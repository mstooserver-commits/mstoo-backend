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

    public function test_admin_login_assets_use_http_when_the_request_is_http()
    {
        config(['app.force_https' => true]);

        $response = $this->get('/admin/auth/login');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('/assets/admin-module/css/style.css', $html);
        $this->assertStringContainsString('/assets/admin-module/css/material-icons.css', $html);
        $this->assertStringNotContainsString('https://localhost/assets/admin-module/css/style.css', $html);
        $this->assertStringNotContainsString('https://127.0.0.1/assets/admin-module/css/style.css', $html);
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
