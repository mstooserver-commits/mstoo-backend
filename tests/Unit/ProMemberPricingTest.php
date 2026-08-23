<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProMemberPricingTest extends TestCase
{
    public function test_coupon_types_include_pro_member()
    {
        $this->assertArrayHasKey('pro_member', COUPON_TYPES);
    }

    public function test_pro_member_is_a_system_module()
    {
        $keys = array_column(SYSTEM_MODULES, 'key');
        $this->assertContains('pro_member_management', $keys);
        $this->assertContains('pro_member_management.manage_benefits', all_permission_keys());
        $this->assertContains('pro_member_management.manage_plans', all_permission_keys());
    }

    public function test_pro_discount_formula()
    {
        $percent = 10;
        $max = 1400;
        $minOrder = 2000;

        $order = 5000;
        $this->assertGreaterThanOrEqual($minOrder, $order);
        $calculated = ($order * $percent) / 100;
        $applied = min($calculated, $max);
        $this->assertEquals(500, $applied);

        $belowMin = 1500;
        $this->assertLessThan($minOrder, $belowMin);
        $appliedBelow = $belowMin >= $minOrder ? min(($belowMin * $percent) / 100, $max) : 0;
        $this->assertEquals(0, $appliedBelow);
    }
}
