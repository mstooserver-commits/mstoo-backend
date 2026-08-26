<?php

namespace Tests\Unit;

use Modules\CustomerModule\Entities\NewsletterSubscriber;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use PHPUnit\Framework\TestCase;

class SubscriptionWalletLoyaltyNewsletterTest extends TestCase
{
    public function test_newsletter_email_is_normalized()
    {
        $this->assertSame('test@email.com', NewsletterSubscriber::normalizeEmail('  Test@Email.com  '));
        $this->assertSame('user@mstoo.test', NewsletterSubscriber::normalizeEmail('USER@MSTOO.TEST'));
    }

    public function test_plan_duration_is_derived_from_unit_and_value()
    {
        $plan = new ProMemberPlan();
        $plan->duration_unit = 'month';
        $plan->duration_value = 1;
        $this->assertEquals(30, $plan->durationInDays());

        $plan->duration_unit = 'year';
        $plan->duration_value = 1;
        $this->assertEquals(365, $plan->durationInDays());

        $plan->duration_unit = 'week';
        $plan->duration_value = 2;
        $this->assertEquals(14, $plan->durationInDays());
    }

    public function test_plan_payable_price_uses_discounted_amount_when_lower()
    {
        $plan = new ProMemberPlan();
        $plan->price = 499;
        $plan->discounted_price = 399;
        $this->assertEquals(399.0, $plan->payablePrice());

        $plan->discounted_price = 0;
        $this->assertEquals(499.0, $plan->payablePrice());
    }

    public function test_subscription_wallet_loyalty_and_newsletter_permissions_exist()
    {
        $keys = array_column(SYSTEM_MODULES, 'key');
        $this->assertContains('pro_member_management', $keys);
        $this->assertContains('newsletter_management', $keys);
        $this->assertContains('pro_member_management.manage_plans', all_permission_keys());
        $this->assertContains('pro_member_management.manage_settings', all_permission_keys());
        $this->assertContains('customer_management.manage_wallet', all_permission_keys());
        $this->assertContains('newsletter_management.view', all_permission_keys());
        $this->assertContains('newsletter_management.create', all_permission_keys());
        $this->assertArrayHasKey('add_fund', WALLET_TRX_TYPE);
        $this->assertArrayHasKey('fund_by_admin', WALLET_TRX_TYPE);
    }

    public function test_plan_benefits_are_configuration_driven()
    {
        $plan = new ProMemberPlan();
        $plan->benefits = ['discount', 'loyalty'];
        $this->assertTrue($plan->includesBenefit('discount'));
        $this->assertFalse($plan->includesBenefit('wallet_bonus'));
    }
}
