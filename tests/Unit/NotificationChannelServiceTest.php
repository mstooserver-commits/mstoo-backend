<?php

namespace Tests\Unit;

use Modules\PromotionManagement\Services\NotificationChannelService;
use Tests\TestCase;

class NotificationChannelServiceTest extends TestCase
{
    public function test_defaults_enable_push_for_customers()
    {
        $service = new NotificationChannelService();
        $matrix = $service->defaults();

        $this->assertSame(1, $matrix['customer']['document_approved']['push']);
        $this->assertSame(0, $matrix['customer']['push_broadcast']['sms']);
        $this->assertTrue($service->enabled('customer', 'document_approved', 'push'));
        $this->assertSame('provider', $service->normalizeAudience('provider-admin'));
    }
}
