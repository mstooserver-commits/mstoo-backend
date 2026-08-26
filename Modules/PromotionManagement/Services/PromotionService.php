<?php

namespace Modules\PromotionManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\CartModule\Entities\Cart;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\PromotionManagement\Entities\Banner;
use Modules\PromotionManagement\Entities\Campaign;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\PromotionManagement\Entities\Discount;
use Modules\PromotionManagement\Entities\WalletBonus;
use Modules\PromotionManagement\Entities\WalletBonusUsage;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

class PromotionService
{
    public function discountAmount($keeper, float $purchaseAmount, bool $enforceMinimum = true): float
    {
        if ($keeper === null) {
            return 0;
        }

        $minPurchase = $enforceMinimum ? (float) ($keeper->min_purchase ?? $keeper->min_add_money_amount ?? 0) : 0;
        if ($purchaseAmount < $minPurchase) {
            return 0;
        }

        $type = $keeper->discount_amount_type ?? $keeper->bonus_amount_type ?? 'percent';
        $value = (float) ($keeper->discount_amount ?? $keeper->bonus_amount ?? 0);
        $max = (float) ($keeper->max_discount_amount ?? $keeper->max_bonus_amount ?? 0);

        if ($type === 'percent') {
            $value = min($value, 100);
            $amount = ($purchaseAmount / 100) * $value;
        } else {
            $amount = $value;
        }

        if ($max > 0 && $amount > $max) {
            $amount = $max;
        }

        return round(max(0, min($amount, $purchaseAmount)), 2);
    }

    public function lineDiscounts(Service $service, float $purchaseAmount): array
    {
        $basic = 0;
        if ($service->relationLoaded('service_discount') && $service->service_discount->count() > 0) {
            $basic = $this->discountAmount($service->service_discount[0]->discount, $purchaseAmount);
        } elseif (optional($service->category)->category_discount && $service->category->category_discount->count() > 0) {
            $basic = $this->discountAmount($service->category->category_discount[0]->discount, $purchaseAmount);
        }

        $campaign = 0;
        if ($service->relationLoaded('campaign_discount') && $service->campaign_discount->count() > 0) {
            $campaign = $this->discountAmount($service->campaign_discount[0]->discount, $purchaseAmount);
        } elseif (optional($service->category)->campaign_discount && $service->category->campaign_discount->count() > 0) {
            $campaign = $this->discountAmount($service->category->campaign_discount[0]->discount, $purchaseAmount);
        }

        if ($campaign >= $basic) {
            return ['basic' => 0.0, 'campaign' => $campaign, 'applicable' => $campaign];
        }

        return ['basic' => $basic, 'campaign' => 0.0, 'applicable' => $basic];
    }

    public function applyCoupon(User $user, string $code, Collection $cartItems): array
    {
        $code = strtoupper(trim($code));
        $query = Coupon::query()->withoutGlobalScopes()
            ->whereRaw('UPPER(coupon_code) = ?', [$code])
            ->where('is_active', 1)
            ->whereHas('discount', function ($query) {
                $query->where('promotion_type', 'coupon')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('is_active', 1);
            })
            ->with(['discount.discount_types']);

        if (should_apply_customer_zone_scope()) {
            $query->whereHas('discount.discount_types', function ($inner) {
                $inner->where(['discount_type' => 'zone', 'type_wise_id' => customer_zone_id()]);
            });
        }

        $coupon = $query->latest()->first();
        if (!$coupon || !$coupon->discount) {
            return ['ok' => false, 'message' => 'not_found'];
        }

        $discount = $coupon->discount;
        if ((int) $discount->total_usage_limit > 0) {
            $used = Booking::query()->where('coupon_code', $coupon->coupon_code)->count();
            if ($used >= (int) $discount->total_usage_limit) {
                return ['ok' => false, 'message' => 'usage_limit'];
            }
        }

        if ($coupon->coupon_type === 'first_booking') {
            $bookings = Booking::query()->where('customer_id', $user->id)->where('booking_status', '!=', 'canceled')->count();
            if ($bookings >= max(1, (int) $discount->limit_per_user)) {
                return ['ok' => false, 'message' => 'not_valid'];
            }
        } elseif ($coupon->coupon_type === 'customer_wise') {
            $allowed = $coupon->coupon_customers()->where('customer_user_id', $user->id)->exists();
            if (!$allowed) {
                return ['ok' => false, 'message' => 'not_valid'];
            }
        } elseif ($coupon->coupon_type === 'pro_member') {
            if (!class_exists(\Modules\ProMemberManagement\Services\ProMemberService::class)
                || !app(\Modules\ProMemberManagement\Services\ProMemberService::class)->couponBenefitEnabled((string) $user->id)) {
                return ['ok' => false, 'message' => 'not_valid'];
            }
        } elseif ((int) $discount->limit_per_user > 0) {
            $limit = Booking::query()->where('customer_id', $user->id)->where('coupon_code', $coupon->coupon_code)->count();
            if ($limit >= (int) $discount->limit_per_user) {
                return ['ok' => false, 'message' => 'not_valid'];
            }
        }

        $eligibleIds = $discount->discount_types
            ->whereIn('discount_type', ['service', 'category'])
            ->pluck('type_wise_id')
            ->filter()
            ->values();

        $cartSubtotal = round((float) $cartItems->sum(function ($item) {
            return (float) $item->service_cost * (int) $item->quantity;
        }), 2);
        if ($cartSubtotal < (float) $discount->min_purchase) {
            return ['ok' => false, 'message' => 'min_purchase'];
        }

        $applied = 0;
        $totalCoupon = 0;
        foreach ($cartItems as $item) {
            if ($eligibleIds->isNotEmpty()
                && !$eligibleIds->contains($item->service_id)
                && !$eligibleIds->contains($item->category_id)) {
                continue;
            }

            $service = Service::query()->find($item->service_id);
            if (!$service) {
                continue;
            }

            $couponAmount = $this->discountAmount($discount, (float) $item->service_cost * (int) $item->quantity, false);
            $basic = (float) $item->discount_amount;
            $campaign = (float) $item->campaign_discount;
            $applicable = $campaign >= $basic ? $campaign : $basic;
            $subtotal = round((float) $item->service_cost * (int) $item->quantity, 2);
            $tax = round((($subtotal - $applicable - $couponAmount) * (float) $service->tax) / 100, 2);

            $item->coupon_discount = $couponAmount;
            $item->coupon_code = $coupon->coupon_code;
            $item->tax_amount = $tax;
            $item->total_cost = round($subtotal - $applicable - $couponAmount + $tax, 2);
            $item->save();
            $applied++;
            $totalCoupon += $couponAmount;
        }

        if (!$applied) {
            return ['ok' => false, 'message' => 'not_valid'];
        }

        return [
            'ok' => true,
            'coupon' => $coupon,
            'coupon_discount' => round($totalCoupon, 2),
            'cart_total' => cart_total($user->id),
        ];
    }

    public function removeCoupon(User $user): void
    {
        foreach (Cart::query()->where('customer_id', $user->id)->get() as $item) {
            $service = Service::query()->find($item->service_id);
            $basic = (float) $item->discount_amount;
            $campaign = (float) $item->campaign_discount;
            $applicable = $campaign >= $basic ? $campaign : $basic;
            $subtotal = round((float) $item->service_cost * (int) $item->quantity, 2);
            $tax = $service
                ? round((($subtotal - $applicable) * (float) $service->tax) / 100, 2)
                : (float) $item->tax_amount;
            $item->coupon_discount = 0;
            $item->coupon_code = null;
            $item->tax_amount = $tax;
            $item->total_cost = round($subtotal - $applicable + $tax, 2);
            $item->save();
        }
    }

    public function matchingWalletBonus(float $addFundAmount): ?WalletBonus
    {
        return WalletBonus::query()->currentlyActive()
            ->where('min_add_money_amount', '<=', $addFundAmount)
            ->get()
            ->sortByDesc(function (WalletBonus $bonus) use ($addFundAmount) {
                return $this->discountAmount($bonus, $addFundAmount);
            })
            ->first();
    }

    public function grantAddFundBonus(User $user, float $addFundAmount, ?string $transactionId = null): float
    {
        $bonus = $this->matchingWalletBonus($addFundAmount);
        if (!$bonus) {
            return 0;
        }

        return DB::transaction(function () use ($user, $addFundAmount, $transactionId, $bonus) {
            $locked = WalletBonus::query()->where('id', $bonus->id)->lockForUpdate()->first();
            if (!$locked) {
                return 0;
            }

            if ((int) $locked->usage_limit > 0 && $locked->usages()->count() >= (int) $locked->usage_limit) {
                return 0;
            }

            $userCount = WalletBonusUsage::query()
                ->where('wallet_bonus_id', $locked->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->count();
            if ((int) $locked->per_user_limit > 0 && $userCount >= (int) $locked->per_user_limit) {
                return 0;
            }

            if ($transactionId && WalletBonusUsage::query()->where('transaction_id', $transactionId)->lockForUpdate()->exists()) {
                return 0;
            }

            $credit = $this->discountAmount($locked, $addFundAmount);
            if ($credit <= 0) {
                return 0;
            }

            $fresh = User::query()->where('id', $user->id)->lockForUpdate()->first();
            $fresh->wallet_balance = round((float) $fresh->wallet_balance + $credit, 2);
            $fresh->save();

            $trx = Transaction::create([
                'ref_trx_id' => $transactionId,
                'booking_id' => null,
                'trx_type' => WALLET_TRX_TYPE['wallet_bonus'] ?? 'wallet_bonus',
                'debit' => 0,
                'credit' => $credit,
                'balance' => $fresh->wallet_balance,
                'from_user_id' => $fresh->id,
                'to_user_id' => $fresh->id,
                'from_user_account' => null,
                'to_user_account' => 'user_wallet',
                'reference_note' => 'Wallet add-fund bonus: ' . $locked->bonus_title,
            ]);

            WalletBonusUsage::create([
                'wallet_bonus_id' => $locked->id,
                'user_id' => $fresh->id,
                'transaction_id' => $transactionId ?: $trx->id,
                'add_fund_amount' => $addFundAmount,
                'bonus_amount' => $credit,
            ]);

            return $credit;
        });
    }

    public function activeWalletBonuses()
    {
        return WalletBonus::query()->currentlyActive()->latest();
    }

    public function activeBanners()
    {
        $query = Banner::query()->withoutGlobalScope('zone_wise_data')->ofStatus(1);

        if (Schema::hasColumn('banners', 'start_date')) {
            $today = now()->toDateString();
            $query->where(function ($inner) use ($today) {
                $inner->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })->where(function ($inner) use ($today) {
                $inner->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
        }

        if (Schema::hasColumn('banners', 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->latest();
    }

    public function activeAdvertisements()
    {
        return Advertisement::query()->currentlyActive()->orderBy('sort_order')->latest();
    }

    public function activeCampaigns()
    {
        return Campaign::query()->ofStatus(1)->latest();
    }

    public function activeDiscounts()
    {
        return Discount::query()
            ->ofPromotionTypes('discount')
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->latest();
    }
}
