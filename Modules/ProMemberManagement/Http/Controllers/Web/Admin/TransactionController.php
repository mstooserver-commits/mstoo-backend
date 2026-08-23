<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMemberTransaction;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $planId = $request->get('plan_id', '');

        $transactions = ProMemberTransaction::query()
            ->with(['customer', 'plan', 'membership'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function ($query) use ($term, $search) {
                    $query->where('id', $search)
                        ->orWhere('gateway_transaction_id', 'like', $term)
                        ->orWhereHas('customer', function ($query) use ($term) {
                            $query->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('payment_status', $status))
            ->when($planId !== '', fn ($query) => $query->where('plan_id', $planId))
            ->latest()
            ->paginate(pagination_limit())
            ->appends($request->query());

        $plans = ProMemberPlan::orderBy('name')->get(['id', 'name']);

        return view('promembermanagement::admin.transactions', compact('transactions', 'plans', 'search', 'status', 'planId'));
    }
}
