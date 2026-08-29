<?php

namespace Tests\Feature;

use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class AdminUpgradeTest extends TestCase
{
    public function test_login_page_uses_mstoo_theme_and_remember_me()
    {
        $response = $this->get('/admin/auth/login');
        $response->assertOk();
        $response->assertSee('login-stage', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('forgot-password', false);
        $response->assertSee('Manage Users & Providers', false);
        $response->assertSee('mstoo-admin.css', false);
        $css = file_get_contents(public_path('assets/admin-module/css/mstoo-admin.css'));
        $this->assertStringContainsString('#D93F46', $css);
        $this->assertStringContainsString('#AC2A29', $css);
    }

    public function test_guest_cannot_open_new_admin_modules()
    {
        $this->get('/admin/service/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/customer/documents')->assertRedirect('admin/auth/login');
        $this->get('/admin/system-setup/ai')->assertRedirect('admin/auth/login');
        $this->get('/admin/push-notification/channels')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_ads_documents_ai_and_channels()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/service/create')->assertOk()->assertSee('Add ads', false);
        $this->actingAs($admin)->get('/admin/service/list')->assertOk()->assertSee('Add ads', false);
        $this->actingAs($admin)->get('/admin/customer/documents')->assertOk();
        $this->actingAs($admin)->get('/admin/system-setup/ai')->assertOk();
        $this->actingAs($admin)->get('/admin/push-notification/channels')->assertOk();
    }
}
