<?php

namespace Modules\PromotionManagement\Services;

use Modules\BusinessSettingsModule\Entities\BusinessSettings;

class NotificationChannelService
{
    public const AUDIENCES = ['customer', 'provider', 'serviceman'];
    public const CHANNELS = ['push', 'email', 'sms'];

    public const TOPICS = [
        'subscription' => 'Subscription',
        'subscription_activated' => 'Subscription Activated',
        'subscription_expired' => 'Subscription Expired',
        'wallet_credit' => 'Wallet Credit',
        'wallet_debit' => 'Wallet Debit',
        'booking_created' => 'Booking Created',
        'booking_confirmed' => 'Booking Confirmed',
        'booking_cancelled' => 'Booking Cancelled',
        'booking_completed' => 'Booking Completed',
        'ad_submitted' => 'Ad Submitted',
        'ad_approved' => 'Ad Approved',
        'ad_rejected' => 'Ad Rejected',
        'ad_featured' => 'Ad Featured',
        'document_submitted' => 'Document Submitted',
        'document_approved' => 'Document Approved',
        'document_rejected' => 'Document Rejected',
        'document_resubmission_required' => 'Resubmission Required',
        'push_broadcast' => 'Push Broadcast',
    ];

    public function defaults(): array
    {
        $matrix = [];
        foreach (self::AUDIENCES as $audience) {
            foreach (self::TOPICS as $topic => $label) {
                $matrix[$audience][$topic] = [
                    'push' => 1,
                    'email' => $topic === 'push_broadcast' ? 0 : 1,
                    'sms' => 0,
                ];
            }
        }
        return $matrix;
    }

    public function matrix(): array
    {
        $stored = $this->row()?->live_values;
        $defaults = $this->defaults();
        if (!is_array($stored)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $stored);
    }

    public function enabled(string $audience, string $topic, string $channel): bool
    {
        $audience = $this->normalizeAudience($audience);
        $matrix = $this->matrix();

        return (int) ($matrix[$audience][$topic][$channel] ?? 0) === 1;
    }

    public function save(array $payload): void
    {
        $matrix = $this->defaults();
        foreach (self::AUDIENCES as $audience) {
            foreach (array_keys(self::TOPICS) as $topic) {
                foreach (self::CHANNELS as $channel) {
                    $matrix[$audience][$topic][$channel] = !empty($payload[$audience][$topic][$channel]) ? 1 : 0;
                }
            }
        }

        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'notification_channel_matrix', 'settings_type' => 'notification_config'],
            [
                'live_values' => $matrix,
                'test_values' => $matrix,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function normalizeAudience(string $userType): string
    {
        return match ($userType) {
            'provider-admin', 'provider' => 'provider',
            'provider-serviceman', 'serviceman' => 'serviceman',
            default => 'customer',
        };
    }

    private function row(): ?BusinessSettings
    {
        try {
            return BusinessSettings::query()
                ->where('key_name', 'notification_channel_matrix')
                ->where('settings_type', 'notification_config')
                ->first();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
