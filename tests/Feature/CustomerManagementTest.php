<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\TransactionModule\Entities\Account;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    public function test_guest_cannot_open_customer_admin_pages()
    {
        $this->get('/admin/customer/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/customer/create')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_customer_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/customer/list')->assertOk();
        $this->actingAs($admin)->get('/admin/customer/create')->assertOk();
        $this->actingAs($admin)->get('/admin/customer/list?status=active&sort=latest&limit=10')->assertOk();
        $this->actingAs($admin)->get('/admin/customer/download')->assertOk();
    }

    public function test_customer_list_search_and_status_tabs_work()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $customer = $this->makeCustomer(['first_name' => 'Filterable', 'last_name' => 'Customer']);
        try {
            $this->actingAs($admin)
                ->get('/admin/customer/list?search=' . urlencode($customer->email))
                ->assertOk()
                ->assertSee('Filterable', false);
            $this->actingAs($admin)->get('/admin/customer/detail/' . $customer->id . '?web_page=overview')->assertOk();
            $this->actingAs($admin)->get('/admin/customer/detail/' . $customer->id . '?web_page=bookings')->assertOk();
            $this->actingAs($admin)->get('/admin/customer/detail/' . $customer->id . '?web_page=wallet')->assertOk();
        } finally {
            $this->cleanupCustomer($customer);
        }
    }

    public function test_restricted_employee_can_view_but_cannot_edit_delete_or_adjust_wallet()
    {
        $role = $this->makeRole(['customer_management.view']);
        $employee = $this->makeEmployee($role);
        $customer = $this->makeCustomer();

        try {
            $this->actingAs($employee)->get('/admin/customer/list')->assertOk();
            $this->actingAs($employee)->get('/admin/customer/detail/' . $customer->id . '?web_page=overview')->assertOk();
            $this->actingAs($employee)->get('/admin/customer/detail/' . $customer->id . '?web_page=bookings')->assertOk();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/customer/create')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/customer/edit/' . $customer->id)->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->put('/admin/customer/update/' . $customer->id, [
                'first_name' => 'Hacked',
                'last_name' => 'User',
                'email' => $customer->email,
                'phone' => $customer->phone,
            ])->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->delete('/admin/customer/delete/' . $customer->id)->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->post('/admin/customer/wallet-adjust/' . $customer->id, [
                'amount' => 10,
                'type' => 'credit',
            ])->assertRedirect('/admin/dashboard');
        } finally {
            $this->cleanupCustomer($customer);
            $this->cleanupEmployee($employee, $role);
        }
    }

    public function test_delete_deactivates_customer_with_wallet_history()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $customer = $this->makeCustomer(['wallet_balance' => 50]);
        try {
            $this->actingAs($admin)->delete('/admin/customer/delete/' . $customer->id);
            $fresh = User::withTrashed()->find($customer->id);
            $this->assertNotNull($fresh);
            $this->assertNull($fresh->deleted_at);
            $this->assertEquals(0, (int)$fresh->is_active);
        } finally {
            $this->cleanupCustomer($customer);
        }
    }

    public function test_delete_removes_customer_without_history()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $customer = $this->makeCustomer();
        $id = $customer->id;
        try {
            $this->actingAs($admin)->delete('/admin/customer/delete/' . $id);
            $this->assertNotNull(User::withTrashed()->find($id)?->deleted_at);
        } finally {
            $leftover = User::withTrashed()->find($id);
            if ($leftover) {
                $this->cleanupCustomer($leftover);
            }
        }
    }

    public function test_customer_list_has_discount_coupon_and_standalone_delete_form()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $customer = $this->makeCustomer(['first_name' => 'Promo', 'last_name' => 'Target']);
        try {
            $this->actingAs($admin)
                ->get('/admin/customer/list?search=' . urlencode($customer->email))
                ->assertOk()
                ->assertSee('admin/discount/create', false)
                ->assertSee('admin/coupon/create', false)
                ->assertSee('id="customer-delete-' . $customer->id . '"', false)
                ->assertSee('/admin/customer/delete/' . $customer->id, false);
        } finally {
            $this->cleanupCustomer($customer);
        }
    }

    public function test_export_does_not_include_password_or_tokens()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $response = $this->actingAs($admin)->get('/admin/customer/download');
        $response->assertOk();
        $this->assertFalse(str_contains(strtolower($response->headers->get('content-disposition', '')), 'password'));
    }

    public function test_manage_wallet_is_a_customer_permission()
    {
        $this->assertContains('customer_management.manage_wallet', all_permission_keys());
    }

    private function makeCustomer(array $overrides = []): User
    {
        $customer = new User();
        $customer->first_name = $overrides['first_name'] ?? 'Cust';
        $customer->last_name = $overrides['last_name'] ?? 'Tester';
        $customer->email = $overrides['email'] ?? ('cust-' . uniqid() . '@mstoo.test');
        $customer->phone = $overrides['phone'] ?? ('+1555' . random_int(1000000, 9999999));
        $customer->password = Hash::make('Password123');
        $customer->user_type = 'customer';
        $customer->is_active = $overrides['is_active'] ?? 1;
        $customer->profile_image = 'def.png';
        $customer->wallet_balance = $overrides['wallet_balance'] ?? 0;
        $customer->save();
        return $customer;
    }

    private function makeRole(array $permissions): Role
    {
        $role = new Role();
        $role->role_name = 'Customer Viewer ' . uniqid();
        $role->is_active = 1;
        sync_role_access_flags($role, $permissions);
        $role->save();
        return $role;
    }

    private function makeEmployee(Role $role): User
    {
        $employee = new User();
        $employee->first_name = 'Restricted';
        $employee->last_name = 'Viewer';
        $employee->email = 'viewer-' . uniqid() . '@mstoo.test';
        $employee->phone = '+1555' . random_int(1000000, 9999999);
        $employee->password = Hash::make('Password123');
        $employee->user_type = 'admin-employee';
        $employee->is_active = 1;
        $employee->profile_image = 'def.png';
        $employee->save();
        $employee->roles()->sync([(string)$role->id]);
        return $employee->fresh(['roles']);
    }

    private function cleanupCustomer(User $customer): void
    {
        Account::query()->where('user_id', $customer->id)->delete();
        $customer->forceDelete();
    }

    private function cleanupEmployee(User $employee, Role $role): void
    {
        $employee->roles()->detach();
        Account::query()->where('user_id', $employee->id)->delete();
        $employee->forceDelete();
        $role->delete();
    }
}
