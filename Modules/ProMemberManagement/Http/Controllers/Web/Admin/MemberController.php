<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Services\ProMemberService;

class MemberController extends Controller
{
    public function __construct(private ProMemberService $service)
    {
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $planId = $request->get('plan_id', '');
        $fromDate = $request->get('from_date', '');
        $toDate = $request->get('to_date', '');
        $dateType = $request->get('date_type', 'starts_at');
        if (!in_array($dateType, ['starts_at', 'expires_at', 'created_at'], true)) {
            $dateType = 'starts_at';
        }

        $this->service->expireDue();

        $members = ProMembership::query()
            ->with(['customer', 'plan'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function ($query) use ($term, $search) {
                    $query->where('id', $search)
                        ->orWhereHas('customer', function ($query) use ($term) {
                            $query->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('id', 'like', $term);
                        });
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($planId !== '', fn ($query) => $query->where('plan_id', $planId))
            ->when($fromDate !== '', fn ($query) => $query->whereDate($dateType, '>=', $fromDate))
            ->when($toDate !== '', fn ($query) => $query->whereDate($dateType, '<=', $toDate))
            ->latest()
            ->paginate(pagination_limit())
            ->appends($request->query());

        $plans = ProMemberPlan::orderBy('name')->get(['id', 'name']);

        return view('promembermanagement::admin.members.index', compact('members', 'plans', 'search', 'status', 'planId', 'fromDate', 'toDate', 'dateType'));
    }

    public function show(string $id)
    {
        $membership = ProMembership::with(['customer', 'plan', 'transactions'])->findOrFail($id);
        $config = $this->service->config();
        $isActive = $membership->isCurrentlyActive();

        return view('promembermanagement::admin.members.show', compact('membership', 'config', 'isActive'));
    }

    public function cancel(string $id): RedirectResponse
    {
        $membership = ProMembership::findOrFail($id);
        if (!in_array($membership->status, ['active', 'pending'], true)) {
            Toastr::error(translate('this_membership_cannot_be_cancelled'));
            return back();
        }

        $membership->status = 'cancelled';
        $membership->cancelled_at = now();
        $membership->save();
        $this->service->forgetMembershipCache($membership->customer_id);
        admin_audit('pro_member.membership_cancelled', $membership, ['customer_id' => $membership->customer_id]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
