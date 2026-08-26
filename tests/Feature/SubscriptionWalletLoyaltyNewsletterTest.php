<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\CustomerModule\Entities\NewsletterSubscriber;
use Modules\CustomerModule\Services\NewsletterService;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Services\ProMemberService;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LoyaltyPointTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class SubscriptionWalletLoyaltyNewsletterTest extends TestCase
{
    public function test_guest_cannot_open_admin_subscription_wallet_loyalty_or_newsletter_pages()
    {
        $this->get('/admin/pro-member/plans')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/members')->assertRedirect('admin/auth/login');
        $this->get('/admin/pro-member/settings')->assertRedirect('admin/auth/login');
        $this->get('/admin/customer/wallet/add-fund')->assertRedirect('admin/auth/login');
        $this->get('/admin/customer/wallet/report')->assertRedirect('admin/auth/login');
        $this->get('/admin/customer/loyalty-point/report')->assertRedirect('admin/auth/login');
        $this->get('/admin/newsletter')->assertRedirect('admin/auth/login');
    }

    public function test_subscription_and_wallet_admin_apis_require_authentication()
    {
        $this->getJson('/api/v1/admin/subscription-package?limit=10&offset=1')->assertStatus(401);
        $this->getJson('/api/v1/admin/subscription-subscriber?limit=10&offset=1')->assertStatus(401);
        $this->getJson('/api/v1/admin/subscription-settings')->assertStatus(401);
        $this->postJson('/api/v1/admin/wallet/add-fund', ['user_id' => 'x', 'amount' => 10])->assertStatus(401);
        $this->getJson('/api/v1/admin/wallet-transaction?limit=10&offset=1')->assertStatus(401);
        $this->getJson('/api/v1/admin/loyalty-point-transaction?limit=10&offset=1')->assertStatus(401);
        $this->getJson('/api/v1/admin/newsletter-subscriber?limit=10&offset=1')->assertStatus(401);
        $this->postJson('/api/v1/customer/subscription/purchase', ['plan_id' => 'x'])->assertStatus(401);
        $this->postJson('/api/v1/customer/wallet/add-fund', ['amount' => 10, 'payment_method' => 'razor_pay'])->assertStatus(401);
        $this->postJson('/api/v1/customer/loyalty-point/wallet-transfer', ['point' => 10])->assertStatus(401);
    }

    public function test_customer_subscription_package_aliases_are_available()
    {
        $this->getJson('/api/v1/customer/pro-member/plans')->assertOk();
        $this->getJson('/api/v1/customer/subscription/packages')->assertOk();
        $this->getJson('/api/v1/customer/pro-member/config')->assertOk();
    }

    public function test_newsletter_rejects_invalid_email()
    {
        $this->postJson('/api/v1/customer/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertStatus(400);
        $this->postJson('/api/v1/customer/newsletter/unsubscribe', ['email' => 'bad'])
            ->assertStatus(400);
        $this->getJson('/api/v1/customer/newsletter/status')->assertStatus(400);
    }

    public function test_newsletter_subscribe_is_case_insensitive_and_can_unsubscribe()
    {
        if (!Schema::hasTable('newsletter_subscribers')) {
            $this->markTestSkipped('newsletter_subscribers table is not migrated.');
        }

        Mail::fake();
        $email = 'Case-' . uniqid() . '@Email.com';
        $normalized = strtolower($email);
        $service = app(NewsletterService::class);

        try {
            $first = $service->subscribe($email, null, 'test');
            $this->assertTrue($first['ok']);
            $second = $service->subscribe(strtoupper($email), null, 'test');
            $this->assertFalse($second['ok']);
            $this->assertEquals('already_subscribed', $second['message']);
            $this->assertEquals(1, NewsletterSubscriber::query()->where('email', $normalized)->count());

            $status = $this->getJson('/api/v1/customer/newsletter/status?email=' . urlencode($email));
            $status->assertOk();
            $this->assertEquals(1, $status->json('content.subscribed'));

            $unsub = $service->unsubscribe($email);
            $this->assertTrue($unsub['ok']);
            $again = $service->unsubscribe($email);
            $this->assertFalse($again['ok']);

            $resub = $service->subscribe($email, null, 'test');
            $this->assertTrue($resub['ok']);
        } finally {
            NewsletterSubscriber::query()->where('email', $normalized)->delete();
        }
    }

    public function test_super_admin_can_open_wallet_loyalty_and_newsletter_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/customer/wallet/add-fund')->assertOk();
        $this->actingAs($admin)->get('/admin/customer/wallet/report')->assertOk();
        $this->actingAs($admin)->get('/admin/customer/loyalty-point/report')->assertOk();
        if (Schema::hasTable('newsletter_subscribers')) {
            $this->actingAs($admin)->get('/admin/newsletter')->assertOk();
        }
    }

    public function test_expired_subscription_is_idempotent_and_removes_benefits()
    {
        if (!Schema::hasTable('pro_memberships')) {
            $this->markTestSkipped('pro_memberships table is not migrated.');
        }

        $service = app(ProMemberService::class);
        $original = $service->config();
        $config = $original;
        $config['enabled'] = 1;
        $config['additional']['grace_period_days'] = 0;
        $config['additional']['notify_email'] = 0;
        $service->saveConfig($config);
        $service->forgetMembershipCache();

        $customer = $this->makeCustomer();
        $plan = $this->makePlan();
        try {
            $membership = $this->makeMembership($customer, $plan, 'active', now()->subDays(40), now()->subDay());
            $first = $service->expireDue();
            $this->assertGreaterThanOrEqual(1, $first);
            $membership->refresh();
            $this->assertEquals('expired', $membership->status);

            $second = $service->expireDue();
            $this->assertEquals(0, ProMembership::query()->where('id', $membership->id)->where('status', 'active')->count());
            $this->assertFalse($service->isProMember($customer->id));
            $this->assertGreaterThanOrEqual(0, $second);
        } finally {
            $this->cleanup($customer, $plan);
            $service->saveConfig($original);
            $service->forgetMembershipCache();
        }
    }

    public function test_wallet_purchase_requires_sufficient_balance_and_creates_ledger()
    {
        if (!Schema::hasTable('pro_memberships') || !Schema::hasTable('transactions')) {
            $this->markTestSkipped('Required tables are not migrated.');
        }

        $service = app(ProMemberService::class);
        $original = $service->config();
        $config = $original;
        $config['enabled'] = 1;
        $config['additional']['purchase_enabled'] = 1;
        $config['additional']['notify_email'] = 0;
        $service->saveConfig($config);

        $customer = $this->makeCustomer(10);
        $plan = $this->makePlan();
        try {
            try {
                $service->purchaseWithWallet($customer, $plan);
                $this->fail('Insufficient wallet should throw.');
            } catch (\RuntimeException $exception) {
                $this->assertEquals('insufficient_wallet_balance', $exception->getMessage());
            }
            $customer->refresh();
            $this->assertEquals(10.0, (float) $customer->wallet_balance);
            $this->assertEquals(0, ProMembership::query()->where('customer_id', $customer->id)->where('status', 'active')->count());

            $customer->wallet_balance = 1000;
            $customer->save();
            $membership = $service->purchaseWithWallet($customer->fresh(), $plan);
            $this->assertEquals('active', $membership->status);
            $this->assertEquals('paid', $membership->payment_status);
            $customer->refresh();
            $this->assertEquals(501.0, (float) $customer->wallet_balance);
            $this->assertTrue(Transaction::query()->where('to_user_id', $customer->id)->where('trx_type', WALLET_TRX_TYPE['pro_membership'])->exists());
        } finally {
            $this->cleanup($customer, $plan);
            $service->saveConfig($original);
            $service->forgetMembershipCache();
        }
    }

    public function test_loyalty_conversion_rolls_points_and_wallet_together()
    {
        if (!Schema::hasTable('loyalty_point_transactions')) {
            $this->markTestSkipped('loyalty_point_transactions table is not migrated.');
        }

        $customer = $this->makeCustomer(0, 50);
        try {
            try {
                loyalty_point_wallet_transfer_transaction($customer->id, 80, 8);
                $this->fail('Insufficient points should throw.');
            } catch (\RuntimeException $exception) {
                $this->assertEquals('insufficient_loyalty_points', $exception->getMessage());
            }
            $customer->refresh();
            $this->assertEquals(50.0, (float) $customer->loyalty_point);
            $this->assertEquals(0.0, (float) $customer->wallet_balance);

            loyalty_point_wallet_transfer_transaction($customer->id, 20, 2);
            $customer->refresh();
            $this->assertEquals(30.0, (float) $customer->loyalty_point);
            $this->assertEquals(2.0, (float) $customer->wallet_balance);
            $this->assertTrue(LoyaltyPointTransaction::query()->where('user_id', $customer->id)->where('transaction_type', 'conversion')->exists());
            $this->assertTrue(Transaction::query()->where('to_user_id', $customer->id)->where('trx_type', TRX_TYPE['loyalty_point_earning'])->exists());
        } finally {
            LoyaltyPointTransaction::query()->where('user_id', $customer->id)->delete();
            Transaction::query()->where('to_user_id', $customer->id)->delete();
            $this->cleanup($customer);
        }
    }

    private function makeCustomer(float $wallet = 0, float $points = 0): User
    {
        $customer = new User();
        $customer->first_name = 'Sub';
        $customer->last_name = 'Tester';
        $customer->email = 'sub-test-' . uniqid() . '@mstoo.test';
        $customer->phone = '+1555' . random_int(1000000, 9999999);
        $customer->password = bcrypt('Password123');
        $customer->user_type = 'customer';
        $customer->is_active = 1;
        $customer->profile_image = 'def.png';
        $customer->wallet_balance = $wallet;
        $customer->loyalty_point = $points;
        $customer->save();
        return $customer;
    }

    private function makePlan(): ProMemberPlan
    {
        $plan = new ProMemberPlan();
        $plan->name = 'Premium ' . uniqid();
        $plan->price = 499;
        $plan->duration_days = 30;
        $plan->duration_unit = 'day';
        $plan->duration_value = 30;
        $plan->benefits = ['discount', 'coupon', 'service_fee'];
        $plan->is_active = 1;
        $plan->save();
        return $plan;
    }

    private function makeMembership(User $customer, ProMemberPlan $plan, string $status, $start, $end): ProMembership
    {
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

    private function cleanup(User $customer, ?ProMemberPlan $plan = null): void
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
