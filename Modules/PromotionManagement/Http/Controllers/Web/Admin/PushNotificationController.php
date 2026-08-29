<?php

namespace Modules\PromotionManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\PromotionManagement\Services\PushNotificationService;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PushNotificationController extends Controller
{
    private PushNotification $pushNotification;
    private Zone $zone;
    private PushNotificationService $notificationService;
    private BusinessSettings $businessSetting;

    public function __construct(
        PushNotification $pushNotification,
        Zone $zone,
        PushNotificationService $notificationService,
        BusinessSettings $businessSetting
    ) {
        $this->pushNotification = $pushNotification;
        $this->zone = $zone;
        $this->notificationService = $notificationService;
        $this->businessSetting = $businessSetting;
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'string' => 'string',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all',
            'to_user_type' => 'required|in:customer,provider,serviceman,all',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $pushNotification = $this->pushNotification
            ->when($request->has('string'), function ($query) use ($request) {
                $keys = explode(' ', base64_decode($request['string']));
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('title', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->when($request->has('to_user_type') && $request['to_user_type'] != 'all', function ($query) use ($request) {
                return $query->whereJsonContains('to_users', $request['to_user_type']);
            })->orderBy('created_at', 'desc')->paginate(pagination_limit(), ['*'], 'offset', $request['offset'])->withPath('');

        $pushNotification->map(function ($query) {
            $query->zone_ids = $this->zone->select('id', 'name')->whereIn('id', $query->zone_ids ?? [])->get();
        });

        return response()->json(response_formatter(DEFAULT_200, $pushNotification), 200);
    }

    public function create(): View|Factory|Application
    {
        $zones = $this->zone->ofStatus(1)->latest()->get();

        return view('promotionmanagement::admin.push-notification.create', compact('zones'));
    }

    public function history(Request $request): View|Factory|Application
    {
        $search = $request->get('search', '');
        $to_user_type = $request->get('to_user_type', 'all');
        $delivery_status = $request->get('delivery_status', 'all');
        $query_param = ['search' => $search, 'to_user_type' => $to_user_type, 'delivery_status' => $delivery_status];

        $pushNotification = $this->pushNotification
            ->with('creator:id,first_name,last_name,email')
            ->when($search !== '', function ($query) use ($search) {
                $keys = explode(' ', $search);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('title', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($to_user_type !== 'all', function ($query) use ($to_user_type) {
                return $query->whereJsonContains('to_users', $to_user_type);
            })
            ->when($delivery_status !== 'all', function ($query) use ($delivery_status) {
                return $query->where('status', $delivery_status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(pagination_limit())
            ->appends($query_param);

        return view('promotionmanagement::admin.push-notification.history', compact(
            'pushNotification',
            'to_user_type',
            'search',
            'delivery_status'
        ));
    }

    public function show(string $id): View|Factory|Application|RedirectResponse
    {
        $pushNotification = $this->pushNotification->with('creator:id,first_name,last_name,email')->where('id', $id)->first();
        if (!$pushNotification) {
            Toastr::error(translate('notification_not_found'));
            return redirect()->route('admin.push-notification.list');
        }

        $zones = $pushNotification->zoneRecords();
        $users = $pushNotification->targetedUsers();

        return view('promotionmanagement::admin.push-notification.show', compact('pushNotification', 'zones', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:200',
            'target_type' => 'required|in:all,zones,users',
            'to_users' => 'required_unless:target_type,users|array',
            'to_users.*' => 'in:customer,provider-admin,provider-serviceman,all',
            'zone_ids' => 'required_if:target_type,zones|array',
            'zone_ids.*' => 'uuid|exists:zones,id',
            'target_user_ids' => 'required_if:target_type,users|array|min:1',
            'target_user_ids.*' => 'uuid|exists:users,id',
            'cover_image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120|dimensions:max_width=5000,max_height=5000',
        ]);

        if (!fcm_is_configured()) {
            Toastr::error(translate('push_notification_is_not_configured'));
            return back()->withInput();
        }

        $imageName = file_uploader('push-notification/', 'png', $request->file('cover_image'));

        $this->notificationService->createAndQueue([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'to_users' => $request->input('to_users', ['customer']),
            'zone_ids' => $request->input('zone_ids', []),
            'target_type' => $request->input('target_type'),
            'target_user_ids' => $request->input('target_user_ids', []),
            'cover_image' => $imageName,
            'created_by' => auth()->id(),
        ]);

        Toastr::success(translate('notification_has_been_queued_for_delivery'));
        return redirect()->route('admin.push-notification.list');
    }

    public function edit(string $id): View|Factory|Application|RedirectResponse
    {
        $pushNotification = $this->pushNotification->where('id', $id)->first();
        if (!$pushNotification) {
            Toastr::error(translate('notification_not_found'));
            return redirect()->route('admin.push-notification.list');
        }

        $zones = $this->zone->ofStatus(1)->latest()->get();

        return view('promotionmanagement::admin.push-notification.edit', compact('pushNotification', 'zones'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:200',
            'to_users' => 'required|array',
            'to_users.*' => 'in:customer,provider-admin,provider-serviceman,all',
            'zone_ids' => 'required|array',
            'zone_ids.*' => 'uuid|exists:zones,id',
            'cover_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120|dimensions:max_width=5000,max_height=5000',
        ]);

        $pushNotification = $this->pushNotification->where(['id' => $id])->first();
        if (!$pushNotification) {
            Toastr::error(translate('notification_not_found'));
            return redirect()->route('admin.push-notification.list');
        }

        $pushNotification->title = $request['title'];
        $pushNotification->description = $request['description'];
        $pushNotification->to_users = $this->notificationService->normalizeUserTypes($request['to_users']);
        $pushNotification->zone_ids = $request['zone_ids'];
        if ($request->hasFile('cover_image')) {
            $pushNotification->cover_image = file_uploader('push-notification/', 'png', $request->file('cover_image'), $pushNotification->cover_image);
        }
        $pushNotification->save();

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $pushNotification = $this->pushNotification->where('id', $id)->first();
        if (isset($pushNotification)) {
            file_remover('push-notification/', $pushNotification['cover_image']);
            $this->pushNotification->where('id', $id)->delete();
        }

        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    public function status_update(Request $request, $id): JsonResponse
    {
        $pushNotification = $this->pushNotification->where('id', $id)->first();
        if (!$pushNotification) {
            return response()->json(['message' => translate('notification_not_found')], 404);
        }

        $this->pushNotification->where('id', $id)->update(['is_active' => !$pushNotification->is_active]);

        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }

    public function download(Request $request): string|StreamedResponse
    {
        $items = $this->pushNotification
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('title', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->when($request->has('to_user_type') && $request['to_user_type'] != 'all', function ($query) use ($request) {
                return $query->whereJsonContains('to_users', $request['to_user_type']);
            })->latest()->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $term = (string) $request->get('q', $request->get('search', ''));

        return response()->json([
            'results' => $this->notificationService->searchUsers($term),
        ]);
    }

    public function previewRecipients(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_type' => 'required|in:all,zones,users',
            'to_users' => 'array',
            'to_users.*' => 'in:customer,provider-admin,provider-serviceman,all',
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
            'target_user_ids' => 'array',
            'target_user_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['count' => 0, 'errors' => error_processor($validator)], 422);
        }

        $count = $this->notificationService->countRecipients(
            $request->input('target_type'),
            $request->input('to_users', []),
            $request->input('zone_ids', []),
            $request->input('target_user_ids', [])
        );

        return response()->json(['count' => $count]);
    }

    public function settings(): View|Factory|Application
    {
        $config = $this->businessSetting->where(['key_name' => 'push_notification', 'settings_type' => 'third_party'])->first();
        $values = $config->live_values ?? [];

        $hasServerKey = !empty($values['server_key']);
        $isEnabled = (int) ($config->is_active ?? 1) === 1 && (string) ($values['status'] ?? '1') !== '0';

        return view('promotionmanagement::admin.push-notification.settings', compact('hasServerKey', 'isEnabled'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:0,1',
            'server_key' => 'nullable|string|max:4000',
        ]);

        $config = $this->businessSetting->where(['key_name' => 'push_notification', 'settings_type' => 'third_party'])->first();
        $existing = $config->live_values ?? [];
        $submittedKey = trim((string) $request->input('server_key', ''));

        if ($this->isMaskedSecret($submittedKey)) {
            $submittedKey = '';
        }

        $serverKey = $submittedKey !== '' ? $submittedKey : ($existing['server_key'] ?? '');
        if ($serverKey === '') {
            return back()->withErrors(['server_key' => translate('firebase_server_key_is_required')]);
        }

        $values = [
            'party_name' => 'push_notification',
            'server_key' => $serverKey,
            'status' => (int) $request->input('status'),
        ];

        $this->businessSetting->updateOrCreate(
            ['key_name' => 'push_notification', 'settings_type' => 'third_party'],
            [
                'key_name' => 'push_notification',
                'live_values' => $values,
                'test_values' => $values,
                'settings_type' => 'third_party',
                'mode' => 'live',
                'is_active' => (int) $request->input('status'),
            ]
        );

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function channels(): View|Factory|Application
    {
        $push = $this->businessSetting->where(['key_name' => 'push_notification', 'settings_type' => 'third_party'])->first();
        $email = $this->businessSetting->where(['key_name' => 'email_config', 'settings_type' => 'email_config'])->first();
        $smsGateways = $this->businessSetting->where('settings_type', 'sms_config')->get();

        $pushValues = $push->live_values ?? [];
        $emailValues = $email->live_values ?? [];
        $channels = [
            [
                'name' => translate('push_notification'),
                'provider' => 'Firebase Cloud Messaging',
                'status' => fcm_is_configured(),
                'configured' => !empty($pushValues['server_key']),
                'settings_url' => route('admin.push-notification.settings'),
            ],
            [
                'name' => translate('email'),
                'provider' => ($emailValues['mailer_name'] ?? $emailValues['driver'] ?? 'SMTP'),
                'status' => (int) ($email->is_active ?? 0) === 1 && !empty($emailValues['host'] ?? null),
                'configured' => !empty($emailValues['host'] ?? null),
                'settings_url' => route('admin.configuration.get-email-config'),
            ],
            [
                'name' => translate('sms'),
                'provider' => $this->activeSmsProvider($smsGateways),
                'status' => $smsGateways->contains(function ($gateway) {
                    return (int) ($gateway->is_active ?? 0) === 1 || (string) (($gateway->live_values['status'] ?? 0)) === '1';
                }),
                'configured' => $smsGateways->isNotEmpty(),
                'settings_url' => route('admin.configuration.sms-get'),
            ],
        ];

        $channelService = app(\Modules\PromotionManagement\Services\NotificationChannelService::class);
        $matrix = $channelService->matrix();
        $topics = \Modules\PromotionManagement\Services\NotificationChannelService::TOPICS;
        $audiences = \Modules\PromotionManagement\Services\NotificationChannelService::AUDIENCES;

        return view('promotionmanagement::admin.push-notification.channels', compact('channels', 'matrix', 'topics', 'audiences'));
    }

    public function saveChannels(Request $request): RedirectResponse
    {
        app(\Modules\PromotionManagement\Services\NotificationChannelService::class)->save($request->input('channels', []));
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    private function activeSmsProvider($gateways): string
    {
        foreach ($gateways as $gateway) {
            $active = (int) ($gateway->is_active ?? 0) === 1 || (string) (($gateway->live_values['status'] ?? 0)) === '1';
            if ($active) {
                return ucfirst((string) $gateway->key_name);
            }
        }

        $first = $gateways->first();
        return $first ? ucfirst((string) $first->key_name) : translate('not_configured');
    }

    private function isMaskedSecret(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^[•*]{6,}$/u', $value);
    }
}
