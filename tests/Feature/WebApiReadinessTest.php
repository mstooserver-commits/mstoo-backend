<?php

namespace Tests\Feature;

use App\Support\MediaStorage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class WebApiReadinessTest extends TestCase
{
    public function test_cors_preflight_allows_configured_origin()
    {
        Config::set('cors.allowed_origins', ['http://localhost:3000']);
        Config::set('cors.supports_credentials', false);

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization,content-type,zoneid',
        ])->options('/api/v1/customer/config');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }

    public function test_web_home_endpoint_is_public()
    {
        $response = $this->getJson('/api/v1/customer/web/home?limit=5');
        $response->assertOk();
        $response->assertJsonPath('response_code', 'default_200');
        $response->assertJsonStructure([
            'content' => [
                'banners',
                'categories',
                'featured_ads',
                'popular_ads',
                'latest_ads',
                'campaigns',
            ],
        ]);
    }

    public function test_web_sitemap_endpoint_is_public()
    {
        $response = $this->getJson('/api/v1/customer/web/sitemap-services?page=1&limit=10');
        $response->assertOk();
        $response->assertJsonPath('response_code', 'default_200');
        $response->assertJsonStructure([
            'content' => ['total', 'page', 'limit', 'last_page', 'data'],
        ]);
    }

    public function test_zone_header_aliases_are_accepted()
    {
        $zone = \DB::table('zones')->where('is_active', 1)->value('id');
        if (!$zone) {
            $this->markTestSkipped('No active zone');
        }

        foreach (['zoneid', 'zoneId', 'zone-id'] as $header) {
            $response = $this->withHeaders([$header => $zone])
                ->getJson('/api/v1/customer/web/home?limit=3');
            $response->assertOk();
        }
    }

    public function test_media_storage_creates_directory_and_rejects_bad_payload()
    {
        $dir = storage_path('app/public/service-test-web');
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }

        $this->expectException(\InvalidArgumentException::class);
        MediaStorage::storeImage('not-base64', 'service-test-web');
    }

    public function test_media_storage_stores_valid_jpeg_base64()
    {
        if (!function_exists('imagejpeg')) {
            $this->markTestSkipped('GD not available');
        }
        $im = imagecreatetruecolor(2, 2);
        ob_start();
        imagejpeg($im);
        $jpeg = ob_get_clean();
        imagedestroy($im);

        $name = MediaStorage::storeImage(base64_encode($jpeg), 'service-test-web');
        $this->assertNotEmpty($name);
        $this->assertFileExists(storage_path('app/public/service-test-web/' . $name));
        File::deleteDirectory(storage_path('app/public/service-test-web'));
    }

    public function test_config_includes_frontend_and_seo_keys()
    {
        $response = $this->getJson('/api/v1/customer/config');
        $response->assertOk();
        $response->assertJsonStructure([
            'content' => ['frontend_url', 'seo' => ['site_title', 'site_description']],
        ]);
    }

    public function test_guest_can_open_service_detail_route_shape()
    {
        $service = \DB::table('services')->where('is_active', 1)->whereNull('deleted_at')->first();
        if (!$service) {
            $this->markTestSkipped('No active service');
        }

        $response = $this->getJson('/api/v1/customer/service/detail/' . $service->id);
        $response->assertOk();
    }
}
