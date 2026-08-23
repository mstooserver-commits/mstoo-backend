<?php

namespace Tests\Feature;

use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    public function test_guest_cannot_open_notification_pages()
    {
        $this->get('/admin/push-notification/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/push-notification/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/push-notification/settings')->assertRedirect('admin/auth/login');
        $this->get('/admin/push-notification/channels')->assertRedirect('admin/auth/login');
        $this->get('/admin/notifications/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/notifications/settings')->assertRedirect('admin/auth/login');
        $this->get('/admin/notifications/channels')->assertRedirect('admin/auth/login');
    }

    public function test_guest_cannot_search_users_or_preview_recipients()
    {
        $this->get('/admin/push-notification/users/search?q=test')->assertRedirect('admin/auth/login');
        $this->post('/admin/push-notification/preview-recipients', [
            'target_type' => 'all',
        ])->assertRedirect('admin/auth/login');
    }

    public function test_guest_cannot_store_notifications()
    {
        $this->post('/admin/push-notification/store', [
            'title' => 'Test',
            'description' => 'Test description',
        ])->assertRedirect('admin/auth/login');
    }

    public function test_admin_can_open_notification_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/push-notification/create')->assertOk()->assertSee('Send notifications', false);
        $this->actingAs($admin)->get('/admin/push-notification/list')->assertOk();
        $this->actingAs($admin)->get('/admin/push-notification/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/push-notification/channels')->assertOk();
        $this->actingAs($admin)->get('/admin/notifications/create')->assertOk();
    }

    public function test_store_validates_required_fields()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)
            ->from('/admin/push-notification/create')
            ->post('/admin/push-notification/store', [])
            ->assertRedirect('/admin/push-notification/create')
            ->assertSessionHasErrors(['title', 'description', 'target_type', 'cover_image']);
    }
}
