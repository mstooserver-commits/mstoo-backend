<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class EmployeeRbacTest extends TestCase
{
    public function test_guest_cannot_open_employee_or_role_pages()
    {
        $this->get('/admin/role/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/role/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/employee/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/employee/create')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_employee_management_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/role/list')->assertOk()->assertSee('Manager', false);
        $this->actingAs($admin)->get('/admin/role/create')->assertOk();
        $this->actingAs($admin)->get('/admin/employee/list')->assertOk();
        $this->actingAs($admin)->get('/admin/employee/create')->assertOk();
    }

    public function test_manager_cannot_access_restricted_admin_urls()
    {
        $employee = $this->makeEmployeeWithRole('Manager');
        if (!$employee) {
            $this->markTestSkipped('Manager role is not available.');
        }

        try {
            $this->actingAs($employee)->get('/admin/customer/list')->assertOk();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/role/list')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/employee/list')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/employee/create')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/business-settings/get-pages-setup')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/blog/create')->assertRedirect('/admin/dashboard');
        } finally {
            $this->cleanupEmployee($employee);
        }
    }

    public function test_support_cannot_access_employee_or_financial_urls()
    {
        $employee = $this->makeEmployeeWithRole('Support');
        if (!$employee) {
            $this->markTestSkipped('Support role is not available.');
        }

        try {
            $this->actingAs($employee)->get('/admin/customer/list')->assertOk();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/role/list')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/employee/list')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/push-notification/create')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/report/booking')->assertRedirect('/admin/dashboard');
        } finally {
            $this->cleanupEmployee($employee);
        }
    }

    public function test_inactive_employee_is_rejected_by_admin_middleware()
    {
        $employee = $this->makeEmployeeWithRole('Support');
        if (!$employee) {
            $this->markTestSkipped('Support role is not available.');
        }

        try {
            $employee->is_active = 0;
            $employee->save();
            $this->actingAs($employee)->get('/admin/dashboard')->assertRedirect('admin/auth/login');
        } finally {
            $this->cleanupEmployee($employee);
        }
    }

    private function makeEmployeeWithRole(string $roleName): ?User
    {
        $role = Role::query()->where('role_name', $roleName)->first();
        if (!$role) {
            return null;
        }

        $employee = new User();
        $employee->first_name = 'RBAC';
        $employee->last_name = $roleName;
        $employee->email = 'rbac-' . strtolower($roleName) . '-' . uniqid() . '@mstoo.test';
        $employee->phone = '+1555' . random_int(1000000, 9999999);
        $employee->password = Hash::make('Password123');
        $employee->user_type = 'admin-employee';
        $employee->is_active = 1;
        $employee->profile_image = 'def.png';
        $employee->save();
        $employee->roles()->sync([$role->id]);

        return $employee->fresh(['roles']);
    }

    private function cleanupEmployee(User $employee): void
    {
        $employee->roles()->detach();
        $employee->forceDelete();
    }
}
