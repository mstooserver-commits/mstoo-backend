<?php

namespace Modules\ProMemberManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\CartModule\Entities\Cart;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Entities\ProMemberTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

class ProMemberService
{
    private array $membershipCache = [];

    public function defaultConfig(): array
    {
        return [
            'enabled' => 0,
            'benefits' => [
                'discount' => [
                    'enabled' => 0,
                    'percent' => 10,
                    'max_amount' => 1400,
                    'min_order' => 2000,
                ],
                'coupon' => ['enabled' => 0],
                'service_fee' => ['enabled' => 0],
            ],
            'additional' => [
                'default_service_fee' => 0,
                'reminder_days' => 3,
                'purchase_enabled' => 1,
            ],
        ];
    }

    public function config(): array
    {
        $row = business_config('pro_member_config', 'pro_member');
        $stored = is_array($row?->live_values) ? $row->live_values : [];
        return array_replace_recursive($this->defaultConfig(), $stored);
    }

    public function saveConfig(array $config): void
    {
        $merged = array_replace_recursive($this->defaultConfig(), $config);
        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'pro_member_config', 'settings_type' => 'pro_member'],
            [
                'live_values' => $merged,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function isFeatureEnabled(): bool
    {
        return (int)($this->config()['enabled'] ?? 0) === 1;
    }

    public function forgetMembershipCache(?string $userId = null): void
    {
        if ($userId) {
            unset($this->membershipCache[$userId]);
            return;
        }

        $this->membershipCache = [];
    }

    public function activeMembership(?string $userId): ?ProMembership
    {
        if (!$userId) {
            return null;
        }
        if (array_key_exists($userId, $this->membershipCache)) {
            return $this->membershipCache[$userId];
        }

        $membership = ProMembership::query()
            ->with('plan')
            ->currentlyActive()
            ->where('customer_id', $userId)
            ->latest('expires_at')
            ->first();

        return $this->membershipCache[$userId] = $membership;
    }

    public function isProMember(?string $userId): bool
    {
        if (!$this->isFeatureEnabled()) {
            return false;
        }

        return (bool)$this->activeMembership($userId);
    }

    public function couponBenefitEnabled(?string $userId): bool
    {
        if (!$this->isProMember($userId)) {
            return false;
        }

        $config = $this->config();
        if (!(int)($config['benefits']['coupon']['enabled'] ?? 0)) {
            return false;
        }

        $membership = $this->activeMembership($userId);
        $plan = $membership?->plan;
        if ($plan && is_array($plan->benefits) && count($plan->benefits) > 0) {
            return $plan->includesBenefit('coupon');
        }

        return true;
    }

    /**
     * Pro automatic discount is skipped when a coupon is already applied so
     * membership discount and coupons do not stack. Service vs campaign still
     * uses MSTOO's existing greater-of-two rule on cart lines.
     */
    public function cartAdjustments(?string $userId, $cartItems = null): array
    {
        $items = $cartItems instanceof Collection ? $cartItems : ($userId ? Cart::where('customer_id', $userId)->get() : collect());
        $subtotal = (float)$items->sum(function ($item) {
            return (float)$item->service_cost * (int)$item->quantity;
        });
        $lineDiscount = (float)$items->sum('discount_amount') + (float)$items->sum('campaign_discount');
        $eligibleAmount = max(0, $subtotal - $lineDiscount);
        $couponDiscount = (float)$items->sum('coupon_discount');
        $hasCoupon = $couponDiscount > 0 || $items->contains(function ($item) {
            return !empty($item->coupon_code);
        });

        $empty = [
            'is_pro_member' => false,
            'subtotal' => round($subtotal, 2),
            'eligible_amount' => round($eligibleAmount, 2),
            'pro_discount' => 0.0,
            'service_fee' => 0.0,
            'has_coupon' => $hasCoupon,
            'coupon_discount' => round($couponDiscount, 2),
            'stacking_rule' => 'Pro automatic discount is not applied when a coupon is already on the cart. Service and campaign discounts keep the greater-of-two rule.',
        ];

        if (!$this->isFeatureEnabled()) {
            return $empty;
        }

        $config = $this->config();
        $defaultFee = max(0, (float)($config['additional']['default_service_fee'] ?? 0));
        $serviceFee = $defaultFee;
        $proDiscount = 0.0;
        $isPro = $this->isProMember($userId);
        $membership = $isPro ? $this->activeMembership($userId) : null;
        $plan = $membership?->plan;

        if ($isPro && $this->planAllows($plan, 'service_fee') && (int)($config['benefits']['service_fee']['enabled'] ?? 0) === 1) {
            $serviceFee = 0.0;
        }

        $discountCfg = $config['benefits']['discount'] ?? [];
        if (
            $isPro
            && (int)($discountCfg['enabled'] ?? 0) === 1
            && $this->planAllows($plan, 'discount')
            && !$hasCoupon
            && $eligibleAmount >= (float)($discountCfg['min_order'] ?? 0)
        ) {
            $percent = min(100, max(0, (float)($discountCfg['percent'] ?? 0)));
            $calculated = ($eligibleAmount * $percent) / 100;
            $maxAmount = max(0, (float)($discountCfg['max_amount'] ?? 0));
            $proDiscount = $maxAmount > 0 ? min($calculated, $maxAmount) : $calculated;
            $proDiscount = round(min($proDiscount, $eligibleAmount), 2);
        }

        return [
            'is_pro_member' => $isPro,
            'subtotal' => round($subtotal, 2),
            'eligible_amount' => round($eligibleAmount, 2),
            'pro_discount' => round($proDiscount, 2),
            'service_fee' => round($serviceFee, 2),
            'has_coupon' => $hasCoupon,
            'coupon_discount' => round($couponDiscount, 2),
            'stacking_rule' => $empty['stacking_rule'],
        ];
    }

    public function payableCartTotal(string $userId): float
    {
        $items = Cart::where('customer_id', $userId)->get();
        $base = (float)$items->sum('total_cost');
        $adj = $this->cartAdjustments($userId, $items);
        return round(max(0, $base - $adj['pro_discount'] + $adj['service_fee']), 2);
    }

    public function allocateToPortion(array $adjustments, float $portionTotal, float $fullTotal): array
    {
        $ratio = $fullTotal > 0 ? ($portionTotal / $fullTotal) : 0;
        return [
            'pro_discount' => round($adjustments['pro_discount'] * $ratio, 2),
            'service_fee' => round($adjustments['service_fee'] * $ratio, 2),
        ];
    }

    public function planPayable(ProMemberPlan $plan): float
    {
        return $plan->payablePrice();
    }

    public function createPendingMembership(User $customer, ProMemberPlan $plan, string $paymentMethod): ProMembership
    {
        return DB::transaction(function () use ($customer, $plan, $paymentMethod) {
            $membership = new ProMembership();
            $membership->customer_id = $customer->id;
            $membership->plan_id = $plan->id;
            $membership->status = 'pending';
            $membership->amount_paid = $this->planPayable($plan);
            $membership->payment_method = $paymentMethod;
            $membership->payment_status = 'pending';
            $membership->auto_renew = 0;
            $membership->save();

            $trx = new ProMemberTransaction();
            $trx->membership_id = $membership->id;
            $trx->customer_id = $customer->id;
            $trx->plan_id = $plan->id;
            $trx->amount = $membership->amount_paid;
            $trx->currency = currency_code();
            $trx->payment_gateway = $paymentMethod;
            $trx->payment_status = 'pending';
            $trx->save();

            return $membership->fresh(['plan', 'transactions']);
        });
    }

    public function activateMembership(ProMembership $membership, ?string $gatewayTransactionId = null, string $paymentStatus = 'paid'): ProMembership
    {
        return DB::transaction(function () use ($membership, $gatewayTransactionId, $paymentStatus) {
            $membership = ProMembership::query()->lockForUpdate()->find($membership->id) ?: $membership;
            if ($membership->status === 'active' && $membership->payment_status === 'paid') {
                return $membership->fresh(['plan', 'customer']);
            }

            $plan = $membership->plan ?: ProMemberPlan::find($membership->plan_id);
            $now = now();
            $existing = $this->latestUsableMembership($membership->customer_id, $membership->id);
            $start = $now;
            $isRenewal = false;
            if ($existing && $existing->expires_at && $existing->expires_at->gt($now)) {
                $start = $existing->expires_at;
                $existing->status = 'cancelled';
                $existing->cancelled_at = $now;
                $existing->save();
                $isRenewal = true;
            }

            $membership->status = 'active';
            $membership->payment_status = $paymentStatus;
            $membership->gateway_transaction_id = $gatewayTransactionId ?: $membership->gateway_transaction_id;
            $membership->starts_at = $start;
            $membership->expires_at = (clone $start)->addDays(max(1, (int)$plan->duration_days));
            $membership->save();

            ProMemberTransaction::query()
                ->where('membership_id', $membership->id)
                ->whereIn('payment_status', ['pending', 'failed'])
                ->update([
                    'payment_status' => $paymentStatus,
                    'gateway_transaction_id' => $gatewayTransactionId,
                ]);

            $this->creditWalletBonus($membership, $plan);
            $this->forgetMembershipCache($membership->customer_id);
            $this->notify($membership->customer, $isRenewal ? 'renewed' : 'purchased', $membership);

            return $membership->fresh(['plan', 'customer']);
        });
    }

    public function failPayment(ProMembership $membership, ?string $reason = null): void
    {
        $membership->payment_status = 'failed';
        $membership->status = 'pending';
        $membership->notes = $reason;
        $membership->save();

        ProMemberTransaction::query()
            ->where('membership_id', $membership->id)
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'failed']);

        $this->notify($membership->customer, 'payment_failed', $membership);
    }

    public function purchaseWithWallet(User $customer, ProMemberPlan $plan): ProMembership
    {
        $amount = $this->planPayable($plan);

        return DB::transaction(function () use ($customer, $plan, $amount) {
            $customer = User::query()->where('id', $customer->id)->lockForUpdate()->first();
            if (!$customer || $customer->wallet_balance < $amount) {
                throw new \RuntimeException('insufficient_wallet_balance');
            }

            $membership = $this->createPendingMembership($customer, $plan, 'wallet_payment');
            $customer->wallet_balance = round($customer->wallet_balance - $amount, 2);
            $customer->save();

            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => WALLET_TRX_TYPE['pro_membership'],
                'debit' => $amount,
                'credit' => 0,
                'balance' => $customer->wallet_balance,
                'from_user_id' => $customer->id,
                'to_user_id' => $customer->id,
                'from_user_account' => 'user_wallet',
                'to_user_account' => 'user_wallet',
                'reference_note' => 'Pro membership ' . $plan->name,
            ]);

            return $this->activateMembership($membership, 'wallet-' . $membership->id, 'paid');
        });
    }

    public function expireDue(): int
    {
        $count = 0;
        ProMembership::query()
            ->with(['customer', 'plan'])
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($memberships) use (&$count) {
                foreach ($memberships as $membership) {
                    $membership->status = 'expired';
                    $membership->save();
                    $this->forgetMembershipCache($membership->customer_id);
                    $this->notify($membership->customer, 'expired', $membership);
                    $count++;
                }
            });

        return $count;
    }

    public function sendExpiryReminders(): int
    {
        $days = max(1, (int)($this->config()['additional']['reminder_days'] ?? 3));
        $count = 0;
        ProMembership::query()
            ->with(['customer', 'plan'])
            ->where('status', 'active')
            ->whereNull('expiry_reminder_sent_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->chunkById(100, function ($memberships) use (&$count) {
                foreach ($memberships as $membership) {
                    $this->notify($membership->customer, 'expiring', $membership);
                    $membership->expiry_reminder_sent_at = now();
                    $membership->save();
                    $count++;
                }
            });

        return $count;
    }

    public function publicConfig(?string $userId = null): array
    {
        try {
            $config = $this->config();
            $membership = $userId ? $this->activeMembership($userId) : null;

            return [
                'enabled' => (int) ($config['enabled'] ?? 0),
                'purchase_enabled' => (int) ($config['additional']['purchase_enabled'] ?? 1),
                'is_pro_member' => $membership ? 1 : 0,
                'membership' => $membership ? [
                    'id' => $membership->id,
                    'status' => $membership->status,
                    'plan_name' => $membership->plan->name ?? null,
                    'starts_at' => optional($membership->starts_at)->toIso8601String(),
                    'expires_at' => optional($membership->expires_at)->toIso8601String(),
                ] : null,
                'benefits' => [
                    'discount' => [
                        'enabled' => (int) ($config['benefits']['discount']['enabled'] ?? 0),
                        'percent' => (float) ($config['benefits']['discount']['percent'] ?? 0),
                        'max_amount' => (float) ($config['benefits']['discount']['max_amount'] ?? 0),
                        'min_order' => (float) ($config['benefits']['discount']['min_order'] ?? 0),
                    ],
                    'coupon' => ['enabled' => (int) ($config['benefits']['coupon']['enabled'] ?? 0)],
                    'service_fee' => ['enabled' => (int) ($config['benefits']['service_fee']['enabled'] ?? 0)],
                ],
                'default_service_fee' => (float) ($config['additional']['default_service_fee'] ?? 0),
                'currency_code' => currency_code(),
                'currency_symbol' => currency_symbol(),
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
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
            ];
        }
    }

    public function notify(?User $user, string $event, ProMembership $membership): void
    {
        if (!$user || empty($user->fcm_token) || !function_exists('device_notification')) {
            return;
        }

        $messages = [
            'purchased' => ['Your Pro membership is now active.', 'Enjoy Pro member benefits on eligible bookings.'],
            'expired' => ['Your Pro membership has expired.', 'Renew a plan to continue enjoying Pro benefits.'],
            'expiring' => ['Your Pro membership expires soon.', 'Renew before it expires to keep your benefits.'],
            'renewed' => ['Your Pro membership has been renewed.', 'Your benefits remain active.'],
            'payment_failed' => ['Your Pro membership payment failed.', 'Please try purchasing again.'],
        ];
        $pair = $messages[$event] ?? null;
        if (!$pair) {
            return;
        }

        device_notification($user->fcm_token, $pair[0], $pair[1], null, $membership->id, 'pro_member');
    }

    private function planAllows(?ProMemberPlan $plan, string $benefit): bool
    {
        if (!$plan || !is_array($plan->benefits) || count($plan->benefits) < 1) {
            return true;
        }

        return $plan->includesBenefit($benefit);
    }

    private function latestUsableMembership(string $customerId, string $exceptId): ?ProMembership
    {
        return ProMembership::query()
            ->where('customer_id', $customerId)
            ->where('id', '!=', $exceptId)
            ->currentlyActive()
            ->latest('expires_at')
            ->first();
    }

    private function creditWalletBonus(ProMembership $membership, ?ProMemberPlan $plan): void
    {
        if (!$plan || $plan->wallet_bonus <= 0) {
            return;
        }
        if (is_array($plan->benefits) && count($plan->benefits) > 0 && !$plan->includesBenefit('wallet_bonus')) {
            return;
        }

        $customer = User::find($membership->customer_id);
        if (!$customer) {
            return;
        }

        $customer->wallet_balance = round($customer->wallet_balance + $plan->wallet_bonus, 2);
        $customer->save();

        Transaction::create([
            'ref_trx_id' => null,
            'booking_id' => null,
            'trx_type' => WALLET_TRX_TYPE['pro_membership_bonus'],
            'debit' => 0,
            'credit' => $plan->wallet_bonus,
            'balance' => $customer->wallet_balance,
            'from_user_id' => $customer->id,
            'to_user_id' => $customer->id,
            'from_user_account' => null,
            'to_user_account' => 'user_wallet',
            'reference_note' => 'Pro membership wallet bonus',
        ]);
    }
}
