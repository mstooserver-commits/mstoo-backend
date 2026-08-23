<?php

namespace Modules\PromotionManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\PromotionManagement\Jobs\SendPushNotificationJob;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class PushNotificationService
{
    public const AUDIENCE_TYPES = ['customer', 'provider-admin', 'provider-serviceman'];

    public function normalizeUserTypes(array $types): array
    {
        $flat = [];
        foreach ($types as $type) {
            if ($type === 'all') {
                return self::AUDIENCE_TYPES;
            }
            $flat[] = $type;
        }

        return array_values(array_unique(array_intersect($flat, self::AUDIENCE_TYPES)));
    }

    public function resolveZoneIds(?array $zoneIds, string $targetType): array
    {
        $zoneIds = array_values(array_filter($zoneIds ?? []));

        if ($targetType === 'all' && empty($zoneIds)) {
            return Zone::query()->ofStatus(1)->pluck('id')->all();
        }

        return $zoneIds;
    }

    public function recipientQuery(array $userTypes, array $zoneIds)
    {
        $userTypes = $this->normalizeUserTypes($userTypes);

        return User::query()
            ->where('is_active', 1)
            ->where(function ($query) use ($userTypes, $zoneIds) {
                if (in_array('customer', $userTypes, true)) {
                    $query->orWhere(function ($customer) use ($zoneIds) {
                        $customer->where('user_type', 'customer');
                        if (!empty($zoneIds)) {
                            $customer->whereHas('zones', function ($zones) use ($zoneIds) {
                                $zones->whereIn('zones.id', $zoneIds);
                            });
                        }
                    });
                }

                if (in_array('provider-admin', $userTypes, true)) {
                    $query->orWhere(function ($provider) use ($zoneIds) {
                        $provider->where('user_type', 'provider-admin');
                        if (!empty($zoneIds)) {
                            $provider->whereHas('provider', function ($relation) use ($zoneIds) {
                                $relation->whereIn('zone_id', $zoneIds);
                            });
                        }
                    });
                }

                if (in_array('provider-serviceman', $userTypes, true)) {
                    $query->orWhere(function ($serviceman) use ($zoneIds) {
                        $serviceman->where('user_type', 'provider-serviceman');
                        if (!empty($zoneIds)) {
                            $serviceman->whereHas('serviceman.provider', function ($relation) use ($zoneIds) {
                                $relation->whereIn('zone_id', $zoneIds);
                            });
                        }
                    });
                }
            });
    }

    public function countRecipients(string $targetType, array $userTypes, array $zoneIds, array $userIds = []): int
    {
        if ($targetType === 'users') {
            return User::query()
                ->whereIn('id', $userIds)
                ->where('is_active', 1)
                ->count();
        }

        return $this->recipientQuery($userTypes, $this->resolveZoneIds($zoneIds, $targetType))->count();
    }

    public function searchUsers(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        return User::query()
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->where('is_active', 1)
            ->whereIn('user_type', self::AUDIENCE_TYPES)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', '%' . $term . '%')
                        ->orWhere('last_name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%')
                        ->orWhere('phone', 'like', '%' . $term . '%')
                        ->orWhere('id', $term);
                });
            })
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(function (User $user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                return [
                    'id' => $user->id,
                    'text' => $name !== '' ? $name : ($user->email ?: $user->phone),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'user_type' => $user->user_type,
                ];
            });
    }

    public function createAndQueue(array $payload): PushNotification
    {
        $targetType = $payload['target_type'] ?? 'zones';
        $userTypes = $this->normalizeUserTypes($payload['to_users'] ?? ['customer']);
        $zoneIds = $this->resolveZoneIds($payload['zone_ids'] ?? [], $targetType);
        $userIds = $targetType === 'users' ? array_values(array_filter($payload['target_user_ids'] ?? [])) : [];

        $notification = new PushNotification();
        $notification->title = $payload['title'];
        $notification->description = $payload['description'];
        $notification->to_users = $userTypes;
        $notification->zone_ids = $zoneIds;
        $notification->target_type = $targetType;
        $notification->target_user_ids = $userIds;
        $notification->cover_image = $payload['cover_image'] ?? null;
        $notification->is_active = 1;
        $notification->status = PushNotification::STATUS_QUEUED;
        $notification->created_by = $payload['created_by'] ?? Auth::id();
        $notification->recipient_count = $this->countRecipients($targetType, $userTypes, $zoneIds, $userIds);
        $notification->pending_count = $notification->recipient_count;
        $notification->save();

        SendPushNotificationJob::dispatch($notification->id);

        return $notification;
    }

    public function deliver(PushNotification $notification): void
    {
        $notification->status = PushNotification::STATUS_SENDING;
        $notification->failure_message = null;
        $notification->save();

        if (!fcm_is_configured()) {
            $this->markFailed($notification, 'Push notifications are not configured.');
            return;
        }

        try {
            $result = $notification->target_type === 'users'
                ? $this->sendToSelectedUsers($notification)
                : $this->sendToAudience($notification);

            $notification->device_count = $result['device_count'];
            $notification->success_count = $result['success_count'];
            $notification->failed_count = $result['failed_count'];
            $notification->invalid_token_count = $result['invalid_token_count'];
            $notification->pending_count = 0;
            $notification->sent_at = now();

            if ($result['success_count'] > 0 && $result['failed_count'] === 0) {
                $notification->status = PushNotification::STATUS_SENT;
            } elseif ($result['success_count'] > 0) {
                $notification->status = PushNotification::STATUS_PARTIALLY_SENT;
                $notification->failure_message = 'Some deliveries could not be completed.';
            } else {
                $notification->status = PushNotification::STATUS_FAILED;
                $notification->failure_message = 'The notification could not be delivered.';
            }

            $notification->save();
        } catch (\Throwable $exception) {
            Log::error('Admin push notification failed', [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);
            $this->markFailed($notification, 'The notification could not be delivered.');
        }
    }

    private function sendToSelectedUsers(PushNotification $notification): array
    {
        $stats = [
            'device_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'invalid_token_count' => 0,
        ];

        User::query()
            ->select('id', 'fcm_token')
            ->whereIn('id', $notification->target_user_ids ?? [])
            ->where('is_active', 1)
            ->orderBy('id')
            ->chunk(100, function ($users) use ($notification, &$stats) {
                foreach ($users as $user) {
                    if (empty($user->fcm_token)) {
                        $stats['failed_count']++;
                        continue;
                    }

                    $stats['device_count']++;
                    $this->sendToToken($user, $notification, $stats);
                }
            });

        return $stats;
    }

    private function sendToAudience(PushNotification $notification): array
    {
        $stats = [
            'device_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'invalid_token_count' => 0,
        ];

        $userTypes = $this->normalizeUserTypes($notification->to_users ?? []);
        $zoneIds = $this->resolveZoneIds($notification->zone_ids ?? [], $notification->target_type ?? 'zones');

        foreach ($userTypes as $userType) {
            foreach ($zoneIds as $zoneId) {
                $result = admin_topic_notification(
                    $userType . '-' . $zoneId,
                    (string) $notification->title,
                    (string) $notification->description,
                    $notification->cover_image,
                    'general'
                );

                $error = fcm_response_error($result);
                if ($error) {
                    $stats['failed_count']++;
                    Log::warning('FCM topic delivery failed', [
                        'notification_id' => $notification->id,
                        'topic' => $userType . '-' . $zoneId,
                        'error' => $error,
                    ]);
                } else {
                    $stats['success_count']++;
                }
            }
        }

        return $stats;
    }

    private function sendToToken(User $user, PushNotification $notification, array &$stats): void
    {
        $result = admin_device_notification(
            (string) $user->fcm_token,
            (string) $notification->title,
            (string) $notification->description,
            $notification->cover_image,
            'general'
        );

        $error = fcm_response_error($result);
        if (!$error) {
            $stats['success_count']++;
            return;
        }

        $stats['failed_count']++;
        if (fcm_error_is_invalid_token($error)) {
            $stats['invalid_token_count']++;
            $user->fcm_token = null;
            $user->save();
        }
    }

    private function markFailed(PushNotification $notification, string $message): void
    {
        $notification->status = PushNotification::STATUS_FAILED;
        $notification->failure_message = $message;
        $notification->pending_count = 0;
        $notification->save();
    }
}
