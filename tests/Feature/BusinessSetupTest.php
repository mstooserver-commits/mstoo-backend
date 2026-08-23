<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\TransactionModule\Entities\Account;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class BusinessSetupTest extends TestCase
{
    public function test_guest_cannot_open_business_setup()
    {
        $this->get('/admin/business-settings/get-business-information')->assertRedirect('admin/auth/login');
        $this->get('/admin/business-settings/404-logs')->assertRedirect('admin/auth/login');
        $this->get('/admin/business-settings/cron-jobs')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_business_setup_tabs()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/business-settings/get-business-information')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=payment')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=bookings')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=providers')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=customers')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=servicemen')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=promotions')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/get-business-information?web_page=business_plan')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/404-logs')->assertOk();
        $this->actingAs($admin)->get('/admin/business-settings/cron-jobs')->assertOk();
    }

    public function test_restricted_employee_can_view_but_cannot_edit()
    {
        $role = $this->makeRole(['system_management.view']);
        $employee = $this->makeEmployee($role);

        try {
            $this->actingAs($employee)->get('/admin/business-settings/get-business-information')->assertOk();
            $this->from('/admin/dashboard')->actingAs($employee)->put('/admin/business-settings/set-maintenance', [
                'status' => 1,
                'message' => 'Down',
            ])->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->post('/admin/business-settings/clear-cache', [
                'target' => 'application',
            ])->assertRedirect('/admin/dashboard');
        } finally {
            $this->cleanupEmployee($employee, $role);
        }
    }

    public function test_maintenance_mode_blocks_customer_api_but_not_admin()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $previous = BusinessSettings::query()->where('key_name', 'maintenance_mode')->where('settings_type', 'system_maintenance')->first();

        try {
            $this->actingAs($admin)->put('/admin/business-settings/set-maintenance', [
                'status' => 1,
                'message' => 'MSTOO is offline for tests',
            ])->assertRedirect();

            $this->getJson('/api/v1/customer/config')->assertStatus(503);
            $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        } finally {
            $this->disableMaintenance();
            if (!$previous) {
                BusinessSettings::query()->where('key_name', 'maintenance_mode')->where('settings_type', 'system_maintenance')->delete();
            }
        }
    }

    public function test_disabled_customer_registration_is_blocked()
    {
        $this->disableMaintenance();

        $row = BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'customer_self_registration', 'settings_type' => 'customer_config'],
            [
                'live_values' => 0,
                'test_values' => 0,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        try {
            $this->postJson('/api/v1/customer/auth/registration', [
                'first_name' => 'Test',
                'phone' => '+1555' . random_int(1000000, 9999999),
                'password' => 'Password123',
                'confirm_password' => 'Password123',
            ])->assertStatus(403);
        } finally {
            $row->live_values = 1;
            $row->test_values = 1;
            $row->save();
        }
    }

    private function disableMaintenance(): void
    {
        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'maintenance_mode', 'settings_type' => 'system_maintenance'],
            [
                'live_values' => [
                    'status' => 0,
                    'message' => 'MSTOO is temporarily unavailable. Please try again later.',
                    'start_at' => null,
                    'end_at' => null,
                ],
                'test_values' => [
                    'status' => 0,
                    'message' => 'MSTOO is temporarily unavailable. Please try again later.',
                    'start_at' => null,
                    'end_at' => null,
                ],
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    private function superAdmin(): ?User
    {
        return User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
    }

    private function makeRole(array $permissions): Role
    {
        $role = new Role();
        $role->role_name = 'Settings Viewer ' . uniqid();
        $role->is_active = 1;
        sync_role_access_flags($role, $permissions);
        $role->save();
        return $role;
    }

    private function makeEmployee(Role $role): User
    {
        $employee = new User();
        $employee->first_name = 'Restricted';
        $employee->last_name = 'Settings';
        $employee->email = 'settings-' . uniqid() . '@mstoo.test';
        $employee->phone = '+1555' . random_int(1000000, 9999999);
        $employee->password = Hash::make('Password123');
        $employee->user_type = 'admin-employee';
        $employee->is_active = 1;
        $employee->profile_image = 'def.png';
        $employee->save();
        $employee->roles()->sync([(string)$role->id]);
        return $employee->fresh(['roles']);
    }

    private function cleanupEmployee(User $employee, Role $role): void
    {
        $employee->roles()->detach();
        Account::query()->where('user_id', $employee->id)->delete();
        $employee->forceDelete();
        $role->delete();
    }
}
