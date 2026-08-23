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
        ]);

        $config = $this->service->config();
        $config['enabled'] = $request->boolean('enabled') ? 1 : 0;
        $config['additional']['purchase_enabled'] = $request->boolean('purchase_enabled') ? 1 : 0;
        $config['additional']['default_service_fee'] = (float)$request->default_service_fee;
        $config['additional']['reminder_days'] = (int)$request->reminder_days;
        $this->service->saveConfig($config);

        admin_audit('pro_member.settings_updated', 'pro_member_config', $config['additional']);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
