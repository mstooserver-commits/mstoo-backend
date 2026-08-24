<?php

namespace Tests\Feature;

use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class PostedAdsListTest extends TestCase
{
    public function test_guest_cannot_open_posted_ads_list_or_excel()
    {
        $this->get('/admin/service/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/service/download')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_posted_ads_list()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)
            ->get('/admin/service/list')
            ->assertOk()
            ->assertSee('Posted ads', false)
            ->assertSee('Ad name', false)
            ->assertSee('Posted by', false)
            ->assertSee('Location', false)
            ->assertSee('Date posted', false)
            ->assertSee('Download Excel', false);
    }

    public function test_super_admin_can_download_posted_ads_excel()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)
            ->get('/admin/service/download')
            ->assertOk();
    }
}
