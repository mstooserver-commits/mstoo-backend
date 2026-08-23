<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProMemberManagement\Services\ProMemberService;

class BenefitController extends Controller
{
    public function __construct(private ProMemberService $service)
    {
    }

    public function index()
    {
        $config = $this->service->config();
        return view('promembermanagement::admin.benefits', compact('config'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'discount_enabled' => 'nullable|in:0,1',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'discount_max_amount' => 'required|numeric|min:0',
            'discount_min_order' => 'required|numeric|min:0',
            'coupon_enabled' => 'nullable|in:0,1',
            'service_fee_enabled' => 'nullable|in:0,1',
        ]);

        $config = $this->service->config();
        $config['benefits']['discount'] = [
            'enabled' => $request->boolean('discount_enabled') ? 1 : 0,
            'percent' => (float)$request->discount_percent,
            'max_amount' => (float)$request->discount_max_amount,
            'min_order' => (float)$request->discount_min_order,
        ];
        $config['benefits']['coupon']['enabled'] = $request->boolean('coupon_enabled') ? 1 : 0;
        $config['benefits']['service_fee']['enabled'] = $request->boolean('service_fee_enabled') ? 1 : 0;
        $this->service->saveConfig($config);

        admin_audit('pro_member.benefits_updated', 'pro_member_config', $config['benefits']);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
