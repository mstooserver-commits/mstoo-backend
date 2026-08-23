<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_catalog_covers_every_system_module()
    {
        $catalog = system_permission_catalog();
        foreach (SYSTEM_MODULES as $module) {
            $this->assertArrayHasKey($module['key'], $catalog);
            $this->assertNotEmpty($catalog[$module['key']]['actions']);
        }
    }

    public function test_permission_keys_use_module_action_format()
    {
        foreach (all_permission_keys() as $key) {
            $this->assertMatchesRegularExpression('/^[a-z_]+\\.[a-z_]+$/', $key);
        }

        $this->assertContains('dashboard.view', all_permission_keys());
        $this->assertContains('employee_management.manage_roles', all_permission_keys());
        $this->assertContains('blog_management.publish', all_permission_keys());
        $this->assertContains('system_management.manage_backup', all_permission_keys());
        $this->assertNotContains('dashboard.create', all_permission_keys());
    }

    public function test_permission_helpers_are_loaded()
    {
        $this->assertTrue(function_exists('access_checker'));
        $this->assertTrue(function_exists('system_permission_catalog'));
        $this->assertTrue(function_exists('all_permission_keys'));
    }
}
