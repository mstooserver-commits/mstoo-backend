<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Modules\CartModule\Entities\Cart;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Services\ProMemberService;
use Modules\TransactionModule\Entities\Account;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class ProMemberManagementTest extends TestCase
{
    public function test_guest_cannot_open_admin_pro_member_pages()
    {
        $this->get('/admin/pro-member/benefits')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/plans')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/members')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/settings')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/transactions')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_pro_member_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/pro-member/benefits')->assertOk();
        $this->actingAs($admin)->get('/admin/pro-member/plans')->assertOk();
        $this->actingAs($admin)->get('/admin/pro-member/plans/create')->assertOk();
        $this->actingAs($admin)->get('/admin/pro-member/members')->assertOk();
        $this->actingAs($admin)->get('/admin/pro-member/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/pro-member/transactions')->assertOk();
    }

    public function test_public_plans_endpoint_is_available()
    {
        $this->getJson('/api/v1/customer/pro-member/plans')->assertOk();
        $this->getJson('/api/v1/customer/pro-member/config')->assertOk();
    }

    public function test_active_pro_member_gets_discount_and_waived_fee()
    {
        $this->withProConfig(function (ProMemberService $service) {
            $customer = $this->makeCustomer();
            $plan = $this->makePlan(['discount', 'service_fee']);
            try {
                $this->makeMembership($customer, $plan, 'active', now()->subDay(), now()->addDays(10));
                $adj = $service->cartAdjustments($customer->id, $this->cartItems(5000));
                $this->assertTrue($adj['is_pro_member']);
                $this->assertEquals(500, $adj['pro_discount']);
                $this->assertEquals(0, $adj['service_fee']);
            } finally {
                $this->cleanupCustomer($customer, $plan);
            }
        });
    }

    public function test_normal_customer_pays_service_fee_without_pro_discount()
    {
        $this->withProConfig(function (ProMemberService $service) {
            $customer = $this->makeCustomer();
            try {
                $adj = $service->cartAdjustments($customer->id, $this->cartItems(5000));
                $this->assertFalse($adj['is_pro_member']);
                $this->assertEquals(0, $adj['pro_discount']);
                $this->assertEquals(100, $adj['service_fee']);
            } finally {
                $this->cleanupCustomer($customer);
            }
        });
    }

    public function test_pro_discount_is_skipped_below_minimum_when_expired_and_when_coupon_is_present()
    {
        $this->withProConfig(function (ProMemberService $service) {
            $customer = $this->makeCustomer();
            $plan = $this->makePlan(['discount', 'service_fee']);
            try {
                $this->makeMembership($customer, $plan, 'active', now()->subDays(20), now()->subDay());
                $expired = $service->cartAdjustments($customer->id, $this->cartItems(5000));
                $this->assertFalse($expired['is_pro_member']);
                $this->assertEquals(0, $expired['pro_discount']);
                $this->assertEquals(100, $expired['service_fee']);

                $this->makeMembership($customer, $plan, 'active', now()->subDay(), now()->addDays(5));
                $belowMin = $service->cartAdjustments($customer->id, $this->cartItems(1500));
                $this->assertTrue($belowMin['is_pro_member']);
                $this->assertEquals(0, $belowMin['pro_discount']);

                $withCoupon = $service->cartAdjustments($customer->id, $this->cartItems(5000, 100, 'PRO10'));
                $this->assertEquals(0, $withCoupon['pro_discount']);
                $this->assertTrue($withCoupon['has_coupon']);
            } finally {
                $this->cleanupCustomer($customer, $plan);
            }
        });
    }

    private function withProConfig(callable $callback): void
    {
        $service = app(ProMemberService::class);
        $original = $service->config();
        $config = $original;
        $config['enabled'] = 1;
        $config['benefits']['discount'] = ['enabled' => 1, 'percent' => 10, 'max_amount' => 1400, 'min_order' => 2000];
        $config['benefits']['coupon']['enabled'] = 1;
        $config['benefits']['service_fee']['enabled'] = 1;
        $config['additional']['default_service_fee'] = 100;
        $service->saveConfig($config);
        $service->forgetMembershipCache();

        try {
            $callback($service);
        } finally {
            $service->saveConfig($original);
            $service->forgetMembershipCache();
        }
    }

    private function cartItems(float $amount, float $coupon = 0, ?string $code = null): Collection
    {
        $item = new Cart();
        $item->service_cost = $amount;
        $item->quantity = 1;
        $item->discount_amount = 0;
        $item->campaign_discount = 0;
        $item->coupon_discount = $coupon;
        $item->coupon_code = $code;
        $item->tax_amount = 0;
        $item->total_cost = $amount;
        return collect([$item]);
    }

    private function makeCustomer(): User
    {
        $customer = new User();
        $customer->first_name = 'Pro';
        $customer->last_name = 'Tester';
        $customer->email = 'pro-test-' . uniqid() . '@mstoo.test';
        $customer->phone = '+1555' . random_int(1000000, 9999999);
        $customer->password = bcrypt('Password123');
        $customer->user_type = 'customer';
        $customer->is_active = 1;
        $customer->profile_image = 'def.png';
        $customer->wallet_balance = 0;
        $customer->save();
        return $customer;
    }

    private function makePlan(array $benefits): ProMemberPlan
    {
        $plan = new ProMemberPlan();
        $plan->name = 'Test Plan ' . uniqid();
        $plan->price = 499;
        $plan->duration_days = 30;
        $plan->benefits = $benefits;
        $plan->is_active = 1;
        $plan->save();
        return $plan;
    }

    private function makeMembership(User $customer, ProMemberPlan $plan, string $status, $start, $end): ProMembership
    {
        ProMembership::query()->where('customer_id', $customer->id)->delete();
        $membership = new ProMembership();
        $membership->customer_id = $customer->id;
        $membership->plan_id = $plan->id;
        $membership->status = $status;
        $membership->starts_at = $start;
        $membership->expires_at = $end;
        $membership->amount_paid = 499;
        $membership->payment_status = 'paid';
        $membership->payment_method = 'wallet_payment';
        $membership->save();
        app(ProMemberService::class)->forgetMembershipCache($customer->id);
        return $membership;
    }

    private function cleanupCustomer(User $customer, ?ProMemberPlan $plan = null): void
    {
        ProMembership::query()->where('customer_id', $customer->id)->delete();
        \Modules\ProMemberManagement\Entities\ProMemberTransaction::query()->where('customer_id', $customer->id)->delete();
        Account::query()->where('user_id', $customer->id)->delete();
        if ($plan) {
            $plan->forceDelete();
        }
        $customer->forceDelete();
        app(ProMemberService::class)->forgetMembershipCache($customer->id);
    }
}
