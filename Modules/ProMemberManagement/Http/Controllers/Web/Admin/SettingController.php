<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProMemberManagement\Services\ProMemberService;

class SettingController extends Controller
{
    public function __construct(private ProMemberService $service)
    {
    }

    public function index()
    {
        $config = $this->service->config();
        return view('promembermanagement::admin.settings', compact('config'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => 'nullable|in:0,1',
            'purchase_enabled' => 'nullable|in:0,1',
            'default_service_fee' => 'required|numeric|min:0',
            'reminder_days' => 'required|integer|min:1|max:30',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
        ]);

        $config = $this->service->config();
        $config['enabled'] = $request->boolean('enabled') ? 1 : 0;
        $config['additional']['purchase_enabled'] = $request->boolean('purchase_enabled') ? 1 : 0;
        $config['additional']['allow_renewal'] = $request->boolean('allow_renewal') ? 1 : 0;
        $config['additional']['allow_cancellation'] = $request->boolean('allow_cancellation') ? 1 : 0;
        $config['additional']['trial_enabled'] = $request->boolean('trial_enabled') ? 1 : 0;
        $config['additional']['auto_renew'] = $request->boolean('auto_renew') ? 1 : 0;
        $config['additional']['notify_email'] = $request->boolean('notify_email') ? 1 : 0;
        $config['additional']['default_service_fee'] = (float)$request->default_service_fee;
        $config['additional']['reminder_days'] = (int)$request->reminder_days;
        $config['additional']['grace_period_days'] = (int) ($request->grace_period_days ?? 0);
        $this->service->saveConfig($config);

        admin_audit('pro_member.settings_updated', 'pro_member_config', $config['additional']);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
