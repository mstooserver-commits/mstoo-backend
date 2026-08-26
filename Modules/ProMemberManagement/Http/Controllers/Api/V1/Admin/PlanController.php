<?php

namespace Modules\ProMemberManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProMemberManagement\Entities\ProMemberPlan;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'nullable|in:active,inactive,all',
            'string' => 'string',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $plans = ProMemberPlan::query()
            ->when($request->filled('string'), function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . base64_decode($request['string']) . '%');
            })
            ->when($request->filled('status') && $request['status'] !== 'all', function ($query) use ($request) {
                $query->ofStatus($request['status'] === 'active' ? 1 : 0);
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $plans), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $plan = new ProMemberPlan();
        $this->fill($plan, $request);
        $plan->save();

        return response()->json(response_formatter(DEFAULT_STORE_200, $plan), 200);
    }

    public function edit(string $id): JsonResponse
    {
        $plan = ProMemberPlan::query()->find($id);
        if (!$plan) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        return response()->json(response_formatter(DEFAULT_200, $plan), 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $plan = ProMemberPlan::query()->find($id);
        if (!$plan) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
        $this->fill($plan, $request);
        $plan->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $plan), 200);
    }

    public function status_update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:0,1',
            'plan_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        ProMemberPlan::query()->whereIn('id', $request['plan_ids'])->update(['is_active' => $request['status']]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        ProMemberPlan::query()->whereIn('id', $request['plan_ids'])->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discounted_price' => 'nullable|numeric|min:0',
            'duration_unit' => 'nullable|in:day,week,month,year',
            'duration_value' => 'nullable|integer|min:1',
            'duration_days' => 'nullable|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'wallet_bonus' => 'nullable|numeric|min:0',
            'loyalty_multiplier' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'benefits' => 'nullable|array',
            'features' => 'nullable|array',
            'is_active' => 'nullable|in:0,1',
        ];
    }

    private function fill(ProMemberPlan $plan, Request $request): void
    {
        $unit = $request['duration_unit'] ?? 'day';
        $value = (int) ($request['duration_value'] ?? $request['duration_days'] ?? 30);
        $map = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365];
        $plan->name = $request['name'];
        $plan->description = $request['description'];
        $plan->price = $request['price'];
        $plan->discounted_price = $request['discounted_price'];
        $plan->duration_unit = $unit;
        $plan->duration_value = $value;
        $plan->duration_days = $value * (int) ($map[$unit] ?? 1);
        $plan->trial_days = (int) ($request['trial_days'] ?? 0);
        $plan->wallet_bonus = $request['wallet_bonus'] ?? 0;
        $plan->loyalty_multiplier = $request['loyalty_multiplier'] ?? 1;
        $plan->sort_order = (int) ($request['sort_order'] ?? 0);
        $plan->benefits = $request['benefits'] ?? ['discount', 'coupon', 'service_fee'];
        $plan->features = $request['features'] ?? [];
        if ($request->has('is_active')) {
            $plan->is_active = (int) $request['is_active'];
        } elseif (!$plan->exists) {
            $plan->is_active = 1;
        }
    }
}
