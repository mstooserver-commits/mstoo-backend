<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_admin_login()
    {
        $response = $this->get('/');

        $response->assertRedirect('admin/auth/login');
    }

    public function test_admin_login_page_is_reachable()
    {
        $response = $this->get('/admin/auth/login');

        $response->assertStatus(200);
        $response->assertSee('MSTOO', false);
    }

    public function test_public_cache_clear_routes_are_removed()
    {
        $this->get('/clear-cache')->assertNotFound();
        $this->get('/clearcache')->assertNotFound();
    }

    public function test_admin_reports_require_authentication()
    {
        $this->get('/admin/report/booking')->assertRedirect('admin/auth/login');
    }

    public function test_admin_api_requires_authentication()
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/test')->assertNotFound();
    }
}
