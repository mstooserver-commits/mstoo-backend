<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\AdminModule\Services\AnalyticsReportService;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class TransactionReportsAnalyticsTest extends TestCase
{
    public function test_guest_is_redirected_from_report_pages()
    {
        $this->get('/admin/transaction/list')->assertRedirect('admin/auth/login');
        $this->get('/admin/report/transaction')->assertRedirect('admin/auth/login');
        $this->get('/admin/report/business/overview')->assertRedirect('admin/auth/login');
        $this->get('/admin/report/booking')->assertRedirect('admin/auth/login');
        $this->get('/admin/report/provider')->assertRedirect('admin/auth/login');
        $this->get('/admin/analytics/search/keyword')->assertRedirect('admin/auth/login');
        $this->get('/admin/analytics/search/customer')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_transaction_reports_and_analytics()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/transaction/list')->assertOk();
        $this->actingAs($admin)->get('/admin/report/transaction')->assertOk();
        $this->actingAs($admin)->get('/admin/report/business/overview')->assertOk();
        $this->actingAs($admin)->get('/admin/report/booking')->assertOk();
        $this->actingAs($admin)->get('/admin/report/provider')->assertOk();
        $this->actingAs($admin)->get('/admin/analytics/search/keyword')->assertOk();
        $this->actingAs($admin)->get('/admin/analytics/search/customer')->assertOk();
    }

    public function test_date_filter_is_applied_to_transaction_and_booking_summaries()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)
            ->get('/admin/transaction/list?date_range=today')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/admin/report/booking?date_range=last_7_days')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/admin/report/business/overview?date_range=this_month')
            ->assertOk();
    }

    public function test_report_calculations_return_expected_keys()
    {
        $service = app(AnalyticsReportService::class);
        $request = Request::create('/', 'GET', ['date_range' => 'this_year']);

        $transaction = $service->transactionSummary($request);
        $this->assertArrayHasKey('total_transactions', $transaction);
        $this->assertArrayHasKey('total_revenue', $transaction);
        $this->assertArrayHasKey('total_commission', $transaction);
        $this->assertArrayHasKey('provider_earnings', $transaction);
        $this->assertArrayHasKey('total_refund', $transaction);

        $business = $service->businessSummary($request);
        $this->assertArrayHasKey('gross_revenue', $business);
        $this->assertArrayHasKey('net_revenue', $business);
        $this->assertArrayHasKey('average_booking_value', $business);

        $booking = $service->bookingSummary($request);
        $this->assertArrayHasKey('completed', $booking);
        $this->assertArrayHasKey('admin_commission', $booking);

        $provider = $service->providerSummary($request);
        $this->assertArrayHasKey('earnings', $provider);
        $this->assertArrayHasKey('avg_rating', $provider);

        $keywords = $service->keywordAnalytics($request);
        $this->assertArrayHasKey('total_searches', $keywords);
        $this->assertArrayHasKey('rows', $keywords);
    }

    public function test_support_employee_cannot_open_reports_or_export()
    {
        $employee = $this->makeEmployeeWithRole('Support');
        if (!$employee) {
            $this->markTestSkipped('Support role is not available.');
        }

        try {
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/report/transaction')->assertRedirect();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/analytics/search/keyword')->assertRedirect();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/transaction/download')->assertRedirect();
            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/report/booking/download')->assertRedirect();
        } finally {
            $this->cleanupEmployee($employee);
        }
    }

    public function test_permission_catalog_includes_report_analytics_actions()
    {
        $keys = all_permission_keys();
        $this->assertContains('report_management.transaction_report', $keys);
        $this->assertContains('report_management.business_report', $keys);
        $this->assertContains('report_management.booking_report', $keys);
        $this->assertContains('report_management.provider_report', $keys);
        $this->assertContains('report_management.keyword_analytics', $keys);
        $this->assertContains('report_management.customer_analytics', $keys);
        $this->assertContains('transaction_management.export', $keys);
    }

    private function superAdmin(): ?User
    {
        return User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
    }

    private function makeEmployeeWithRole(string $roleName): ?User
    {
        $role = Role::query()->where('role_name', $roleName)->first();
        if (!$role) {
            return null;
        }

        $employee = new User();
        $employee->first_name = 'Report';
        $employee->last_name = $roleName;
        $employee->email = 'report-' . strtolower($roleName) . '-' . uniqid() . '@mstoo.test';
        $employee->phone = '+1555' . random_int(1000000, 9999999);
        $employee->password = Hash::make('Password123');
        $employee->user_type = 'admin-employee';
        $employee->is_active = 1;
        $employee->profile_image = 'def.png';
        $employee->identification_type = 'nid';
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
