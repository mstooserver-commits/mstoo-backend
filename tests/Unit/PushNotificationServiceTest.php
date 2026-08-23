<?php

namespace Tests\Unit;

use Modules\PromotionManagement\Services\PushNotificationService;
use PHPUnit\Framework\TestCase;

class PushNotificationServiceTest extends TestCase
{
    public function test_it_expands_all_user_types()
    {
        $service = new PushNotificationService();

        $this->assertSame(
            ['customer', 'provider-admin', 'provider-serviceman'],
            $service->normalizeUserTypes(['all'])
        );
    }

    public function test_it_keeps_only_supported_user_types()
    {
        $service = new PushNotificationService();

        $this->assertSame(
            ['customer', 'provider-admin'],
            $service->normalizeUserTypes(['customer', 'provider-admin', 'super-admin', 'customer'])
        );
    }
}
