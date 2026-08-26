<?php

namespace Modules\ProMemberManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProMemberManagement\Services\ProMemberService;

class SettingController extends Controller
{
    public function __construct(private ProMemberService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(response_formatter(DEFAULT_200, $this->service->config()), 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'nullable|in:0,1',
            'purchase_enabled' => 'nullable|in:0,1',
            'allow_renewal' => 'nullable|in:0,1',
            'allow_cancellation' => 'nullable|in:0,1',
            'trial_enabled' => 'nullable|in:0,1',
            'auto_renew' => 'nullable|in:0,1',
            'notify_email' => 'nullable|in:0,1',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'reminder_days' => 'nullable|integer|min:1|max:30',
            'default_service_fee' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $config = $this->service->config();
        foreach (['enabled'] as $key) {
            if ($request->has($key)) {
                $config[$key] = (int) $request[$key];
            }
        }
        foreach (['purchase_enabled', 'allow_renewal', 'allow_cancellation', 'trial_enabled', 'auto_renew', 'notify_email', 'grace_period_days', 'reminder_days', 'default_service_fee'] as $key) {
            if ($request->has($key)) {
                $config['additional'][$key] = is_numeric($request[$key]) ? $request[$key] + 0 : (int) $request[$key];
            }
        }
        $this->service->saveConfig($config);

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $config), 200);
    }
}
