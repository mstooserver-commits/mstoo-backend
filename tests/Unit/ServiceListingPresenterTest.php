<?php

namespace Tests\Unit;

use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceListingPresenter;
use Tests\TestCase;

class ServiceListingPresenterTest extends TestCase
{
    public function test_display_price_includes_rupee_and_unit()
    {
        $presenter = new ServiceListingPresenter();

        $this->assertSame('₹25,000/day', $presenter->formatDisplayPrice(25000, '₹', 'day'));
        $this->assertSame('₹849/day', $presenter->formatDisplayPrice(849, '₹', 'day'));
        $this->assertSame('₹1,250.50/day', $presenter->formatDisplayPrice(1250.50, '₹', 'day'));
    }

    public function test_price_unit_normalizes_rent_duration()
    {
        $presenter = new ServiceListingPresenter();

        $this->assertSame('day', $presenter->priceUnit('per day'));
        $this->assertSame('day', $presenter->priceUnit('/day'));
        $this->assertSame('hour', $presenter->priceUnit('Hourly'));
        $this->assertSame('week', $presenter->priceUnit('per week'));
        $this->assertSame('month', $presenter->priceUnit('monthly'));
    }

    public function test_decorate_one_fills_listing_fields_when_zone_variations_are_empty()
    {
        $presenter = new ServiceListingPresenter();
        $service = new Service();
        $service->id = 'listing-test-service';
        $service->cover_image = 'lehnga.png';
        $service->thumbnail = 'lehnga.png';

        $variation = new Variation([
            'variant' => 'per day',
            'variant_key' => 'per day',
            'zone_id' => 'other-zone',
            'price' => 25000,
            'service_id' => $service->id,
        ]);

        config(['zone_id' => 'chd-zone']);
        $presenter->decorateOne($service, collect([$variation]));

        $this->assertSame(25000.0, $service->min_price);
        $this->assertSame('₹25,000/day', $service->display_price);
        $this->assertSame(25000.0, $service->price);
        $this->assertSame('₹', $service->currency_symbol);
        $this->assertSame('day', $service->price_unit);
        $this->assertSame(25000.0, $service->variations_app_format['default_price']);
        $this->assertSame('₹25,000/day', $service->variations_app_format['display_price']);
        $this->assertNotEmpty($service->variations_app_format['zone_wise_variations']);
        $this->assertSame('lehnga.png', $service->cover_image);
        $this->assertNull($service->short_description);
        $this->assertContains('variations', $service->getHidden());
        $this->assertContains('description', $service->getHidden());
        $this->assertStringContainsString('lehnga.png', (string) $service->cover_image_full_url);
        $this->assertStringContainsString('/storage/', (string) $service->cover_image_full_url);
    }

    public function test_placeholder_cover_has_no_image_url()
    {
        $presenter = new ServiceListingPresenter();

        $this->assertNull($presenter->imageUrl('def.png'));
        $this->assertNull($presenter->imageUrl(null));
        $this->assertSame('https://cdn.example.com/ad.jpg', $presenter->imageUrl('https://cdn.example.com/ad.jpg'));
    }
}
