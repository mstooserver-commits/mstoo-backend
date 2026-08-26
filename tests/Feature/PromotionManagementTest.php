<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    public function test_guest_cannot_open_promotion_admin_pages()
    {
        $this->get('/admin/discount/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/coupon/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/campaign/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/wallet-bonus/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/advertisement/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/banner/create')->assertRedirect('admin/auth/login');
    }

    public function test_guest_cannot_store_wallet_bonus_or_advertisement()
    {
        $this->post('/admin/wallet-bonus/store', [
            'bonus_title' => 'Test',
            'bonus_amount_type' => 'amount',
            'bonus_amount' => 10,
        ])->assertRedirect('admin/auth/login');

        $this->post('/admin/advertisement/store', [
            'title' => 'Test ad',
            'resource_type' => 'link',
        ])->assertRedirect('admin/auth/login');
    }

    public function test_admin_can_open_promotion_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/discount/list')->assertOk();
        $this->actingAs($admin)->get('/admin/coupon/list')->assertOk();
        $this->actingAs($admin)->get('/admin/campaign/list')->assertOk();
        $this->actingAs($admin)->get('/admin/wallet-bonus/list')->assertOk();
        $this->actingAs($admin)->get('/admin/advertisement/create')->assertOk();
        $this->actingAs($admin)->get('/admin/banner/create')->assertOk();
    }

    public function test_customer_banner_advertisement_and_wallet_bonus_apis_return_ok()
    {
        $this->getJson('/api/v1/customer/banner')->assertOk();
        $this->getJson('/api/v1/customer/discount')->assertOk();
        $this->getJson('/api/v1/customer/campaign')->assertOk();

        if (Schema::hasTable('advertisements')) {
            $this->getJson('/api/v1/customer/advertisement')->assertOk();
        }
        if (Schema::hasTable('wallet_bonuses')) {
            $this->getJson('/api/v1/customer/wallet-bonus-list')->assertOk();
            $this->getJson('/api/v1/customer/bonus-list')->assertOk();
        }
    }

    public function test_coupon_apply_requires_authentication()
    {
        $this->postJson('/api/v1/customer/coupon/apply', [
            'coupon_code' => 'SAVE10',
        ])->assertStatus(401);
    }

    public function test_admin_promotion_apis_require_authentication()
    {
        $this->getJson('/api/v1/admin/wallet-bonus?limit=10&offset=1&status=all')->assertStatus(401);
        $this->getJson('/api/v1/admin/advertisement?limit=10&offset=1&status=all&resource_type=all')->assertStatus(401);
        $this->getJson('/api/v1/admin/discount?limit=10&offset=1&status=all&discount_type=all')->assertStatus(401);
        $this->getJson('/api/v1/admin/coupon?limit=10&offset=1&status=all&coupon_type=all')->assertStatus(401);
    }

    public function test_wallet_bonus_history_requires_authentication()
    {
        $this->getJson('/api/v1/customer/wallet-bonus/history')->assertStatus(401);
    }
}
