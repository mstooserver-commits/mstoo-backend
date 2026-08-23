<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProMemberManagement\Entities\ProMemberPlan;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');

        $plans = ProMemberPlan::query()
            ->withCount(['activeMemberships as active_members_count'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where('name', 'like', $term);
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->ofStatus($status === 'active' ? 1 : 0);
            })
            ->latest()
            ->paginate(pagination_limit())
            ->appends($request->query());

        return view('promembermanagement::admin.plans.index', compact('plans', 'search', 'status'));
    }

    public function create()
    {
        $plan = null;
        return view('promembermanagement::admin.plans.form', compact('plan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $plan = new ProMemberPlan();
        $this->fill($plan, $data, $request);
        $plan->save();
        admin_audit('pro_member.plan_created', $plan, ['name' => $plan->name]);
        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.pro-member.plans.index');
    }

    public function show(string $id)
    {
        $plan = ProMemberPlan::withCount(['memberships', 'activeMemberships as active_members_count'])->findOrFail($id);
        return view('promembermanagement::admin.plans.show', compact('plan'));
    }

    public function edit(string $id)
    {
        $plan = ProMemberPlan::findOrFail($id);
        return view('promembermanagement::admin.plans.form', compact('plan'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $plan = ProMemberPlan::findOrFail($id);
        $this->fill($plan, $this->validated($request, $id), $request);
        $plan->save();
        admin_audit('pro_member.plan_updated', $plan, ['name' => $plan->name]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return redirect()->route('admin.pro-member.plans.index');
    }

    public function status(string $id): JsonResponse
    {
        $plan = ProMemberPlan::findOrFail($id);
        $plan->is_active = $plan->is_active ? 0 : 1;
        $plan->save();
        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }

    public function destroy(string $id): RedirectResponse
    {
        $plan = ProMemberPlan::withCount('memberships')->findOrFail($id);
        $hasHistory = $plan->memberships_count > 0
            || \Modules\ProMemberManagement\Entities\ProMemberTransaction::query()->where('plan_id', $plan->id)->exists();
        if ($hasHistory) {
            $plan->is_active = 0;
            $plan->save();
            Toastr::warning(translate('this_plan_has_membership_records_so_it_was_deactivated_instead_of_deleted'));
            return back();
        }

        $plan->delete();
        admin_audit('pro_member.plan_deleted', $plan, ['name' => $plan->name]);
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    private function validated(Request $request, ?string $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'discounted_price' => 'nullable|numeric|min:0',
            'duration_days' => 'required|integer|min:1|max:3650',
            'wallet_bonus' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|in:0,1',
            'benefits' => 'nullable|array',
            'benefits.*' => 'in:discount,coupon,service_fee,wallet_bonus',
        ]);
    }

    private function fill(ProMemberPlan $plan, array $data, Request $request): void
    {
        $plan->name = $data['name'];
        $plan->description = $data['description'] ?? null;
        $plan->price = $data['price'];
        $plan->discounted_price = isset($data['discounted_price']) && $data['discounted_price'] !== '' ? $data['discounted_price'] : null;
        $plan->duration_days = $data['duration_days'];
        $plan->wallet_bonus = $data['wallet_bonus'] ?? 0;
        $plan->benefits = $data['benefits'] ?? ['discount', 'coupon', 'service_fee'];
        $plan->is_active = $request->boolean('is_active') ? 1 : 0;
    }
}
