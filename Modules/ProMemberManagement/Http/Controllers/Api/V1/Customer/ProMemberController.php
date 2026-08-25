<?php

namespace Modules\ProMemberManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Entities\ProMemberTransaction;
use Modules\ProMemberManagement\Services\ProMemberService;

class ProMemberController extends Controller
{
    private function service(): ProMemberService
    {
        return app(ProMemberService::class);
    }

    public function config(Request $request): JsonResponse
    {
        try {
            $user = null;
            try {
                $user = $request->user('api');
                if (!$user) {
                    $user = $request->user();
                }
            } catch (\Throwable $exception) {
                $user = null;
            }
            $userId = $user ? $user->id : null;

            return response()->json(response_formatter(
                DEFAULT_200,
                $this->service()->publicConfig($userId !== null ? (string) $userId : null)
            ), 200);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(response_formatter(DEFAULT_200, [
                'enabled' => 0,
                'purchase_enabled' => 0,
                'is_pro_member' => 0,
                'membership' => null,
                'benefits' => [
                    'discount' => ['enabled' => 0, 'percent' => 0, 'max_amount' => 0, 'min_order' => 0],
                    'coupon' => ['enabled' => 0],
                    'service_fee' => ['enabled' => 0],
                ],
                'default_service_fee' => 0,
                'currency_code' => 'INR',
                'currency_symbol' => '₹',
            ]), 200);
        }
    }

    public function plans(): JsonResponse
    {
        if (!$this->service()->isFeatureEnabled()) {
            return response()->json(response_formatter(DEFAULT_200, []), 200);
        }

        $plans = ProMemberPlan::query()->ofStatus(1)->latest()->get()->map(function (ProMemberPlan $plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'discounted_price' => $plan->discounted_price,
                'payable_price' => $plan->payablePrice(),
                'duration_days' => $plan->duration_days,
                'benefits' => $plan->benefits ?? [],
                'wallet_bonus' => $plan->wallet_bonus,
                'currency_code' => currency_code(),
            ];
        });

        return response()->json(response_formatter(DEFAULT_200, $plans), 200);
    }

    public function current(Request $request): JsonResponse
    {
        $this->service()->expireDue();
        $membership = $this->service()->activeMembership($request->user()->id);
        return response()->json(response_formatter(DEFAULT_200, [
            'is_pro_member' => $membership ? 1 : 0,
            'membership' => $membership,
            'adjustments_preview' => $this->service()->cartAdjustments($request->user()->id),
        ]), 200);
    }

    public function purchase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|uuid',
            'payment_method' => 'required|in:wallet_payment,razor_pay',
            'callback' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $config = $this->service()->config();
        if (!(int)$config['enabled'] || !(int)$config['additional']['purchase_enabled']) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $plan = ProMemberPlan::query()->ofStatus(1)->where('id', $request->plan_id)->first();
        if (!$plan) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $customer = $request->user();

        if ($request->payment_method === 'wallet_payment') {
            try {
                $membership = $this->service()->purchaseWithWallet($customer, $plan);
            } catch (\RuntimeException $exception) {
                return response()->json(response_formatter(DEFAULT_400, ['message' => $exception->getMessage()]), 400);
            }

            return response()->json(response_formatter(DEFAULT_STORE_200, $membership), 200);
        }

        $membership = $this->service()->createPendingMembership($customer, $plan, 'razor_pay');
        $query = http_build_query(array_filter([
            'access_token' => base64_encode($customer->id),
            'membership_id' => $membership->id,
            'callback' => $request->callback,
        ]));

        return response()->json(response_formatter(DEFAULT_200, [
            'membership' => $membership,
            'amount' => $membership->amount_paid,
            'currency' => currency_code(),
            'payment_url' => url('/payment/pro-member/razor-pay?' . $query),
        ]), 200);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $rows = ProMemberTransaction::query()
            ->with('plan')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $rows), 200);
    }
}
