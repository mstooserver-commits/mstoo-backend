<?php

namespace Tests\Feature;

use Modules\ServiceManagement\Entities\Service;
use Tests\TestCase;

class ListingPriceTest extends TestCase
{
    public function test_customer_listing_includes_display_price_and_image_url()
    {
        $service = Service::query()
            ->withoutGlobalScopes()
            ->where('is_active', 1)
            ->whereHas('variations')
            ->latest()
            ->first();

        if (!$service) {
            $this->markTestSkipped('No active ads with prices are available.');
        }

        $response = $this->getJson('/api/v1/customer/service?limit=10&offset=1');
        $response->assertOk();
        $response->assertJsonPath('response_code', 'default_200');

        $items = collect($response->json('content.data') ?? $response->json('content'));
        if ($items->isEmpty()) {
            $this->markTestSkipped('Customer listing returned no ads.');
        }

        $first = collect($items->first());
        $display = (string) ($first->get('display_price') ?: $first->get('price') ?: data_get($first, 'variations_app_format.display_price'));
        $this->assertNotEmpty($display);
        $this->assertStringContainsString('₹', $display);
        $this->assertSame('₹', $first->get('currency_symbol') ?: data_get($first, 'variations_app_format.currency_symbol'));
        $this->assertGreaterThan(0, (float) ($first->get('min_price') ?: data_get($first, 'variations_app_format.default_price')));
        $cover = (string) $first->get('cover_image');
        if ($cover !== '' && !in_array($cover, ['def.png', 'default.png'], true)) {
            $this->assertFalse(str_starts_with($cover, 'http'), 'cover_image must stay a filename so the app can prefix image_base_url.');
        }
        $this->assertArrayNotHasKey('variations', $first->all());
        $this->assertTrue(
            !array_key_exists('description', $first->all()) || $first->get('description') === null,
            'Listing cards should not include a description block.'
        );
    }

    public function test_customer_config_sends_rupee_on_the_left()
    {
        $response = $this->getJson('/api/v1/customer/config');
        $response->assertOk();

        $content = $response->json('content');
        $this->assertSame('₹', $content['currency_symbol'] ?? null);
        $this->assertSame('INR', $content['currency_code'] ?? null);
        $this->assertSame('left', $content['currency_symbol_position'] ?? null);
        $this->assertSame(0, (int) ($content['currency_decimal_point'] ?? -1));
        $this->assertStringContainsString('/storage/', (string) ($content['image_base_url'] ?? ''));
    }

    public function test_service_images_are_served_from_storage()
    {
        $files = glob(storage_path('app/public/service/*.{jpg,jpeg,png,webp,JPG,PNG}'), GLOB_BRACE);
        if (!$files) {
            $files = glob(storage_path('app/public/service/*'));
        }
        $file = collect($files ?: [])->first(function ($path) {
            return is_file($path);
        });
        if (!$file) {
            $this->markTestSkipped('No service images are stored locally.');
        }

        $name = basename($file);
        $this->get('/storage/app/public/service/' . $name)->assertOk();
        $this->get('/storage/service/' . $name)->assertOk();
    }
}
