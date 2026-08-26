<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CartModule\Entities\Cart;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\PromotionManagement\Services\PromotionService;

class CouponController extends Controller
{
    public function __construct(private Coupon $coupon, private Cart $cart, private PromotionService $promotions)
    {
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $proCouponAllowed = false;
        try {
            if (class_exists(\Modules\ProMemberManagement\Services\ProMemberService::class)) {
                $userId = auth('api')->id();
                $proCouponAllowed = app(\Modules\ProMemberManagement\Services\ProMemberService::class)
                    ->couponBenefitEnabled($userId !== null ? (string) $userId : null);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $applyZone = function ($query) {
            if (should_apply_customer_zone_scope()) {
                $query->whereHas('discount.discount_types', function ($inner) {
                    $inner->where(['discount_type' => 'zone', 'type_wise_id' => customer_zone_id()]);
                });
            }
        };

        $active_coupons = $this->coupon->with(['discount'])
            ->when(!is_null($request->status), function ($query) use ($request) {
                $query->ofStatus(1);
            })
            ->when(!$proCouponAllowed, function ($query) {
                $query->where('coupon_type', '!=', 'pro_member');
            })
            ->whereHas('discount', function ($query) {
                $query->where(['promotion_type' => 'coupon'])
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('is_active', 1);
            })
            ->when(true, $applyZone)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $expired_coupons = $this->coupon->with(['discount'])
            ->when(!is_null($request->status), function ($query) use ($request) {
                $query->ofStatus(1);
            })
            ->when(!$proCouponAllowed, function ($query) {
                $query->where('coupon_type', '!=', 'pro_member');
            })
            ->whereHas('discount', function ($query) {
                $query->where(['promotion_type' => 'coupon'])
                    ->whereDate('end_date', '<', now())
                    ->where('is_active', 1);
            })
            ->when(true, $applyZone)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, ['active_coupons' => $active_coupons, 'expired_coupons' => $expired_coupons]), 200);
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function apply_coupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $cartItems = $this->cart->where(['customer_id' => $request->user()->id])->get();
        if ($cartItems->isEmpty()) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $result = $this->promotions->applyCoupon($request->user(), (string) $request['coupon_code'], $cartItems);
        if (!$result['ok']) {
            if ($result['message'] === 'not_found') {
                return response()->json(response_formatter(DEFAULT_404), 200);
            }
            return response()->json(response_formatter(COUPON_NOT_VALID_FOR_CART), 200);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'coupon_discount' => $result['coupon_discount'],
            'cart_total' => $result['cart_total'],
            'coupon_code' => $result['coupon']->coupon_code,
        ]), 200);
    }

    public function remove_coupon(Request $request): JsonResponse
    {
        $cartItems = $this->cart->where('customer_id', $request->user()->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $this->promotions->removeCoupon($request->user());

        return response()->json(response_formatter(DEFAULT_UPDATE_200, [
            'cart_total' => cart_total($request->user()->id),
        ]), 200);
    }
}
