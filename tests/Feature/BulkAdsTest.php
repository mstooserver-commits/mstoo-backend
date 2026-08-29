<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\PostedAdService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class BulkAdsTest extends TestCase
{
    public function test_guest_cannot_open_bulk_ads()
    {
        $this->get('/admin/service/bulk')->assertRedirect('admin/auth/login');
        $this->post('/admin/service/bulk')->assertRedirect('admin/auth/login');
        $this->post('/admin/service/bulk/import')->assertRedirect('admin/auth/login');
    }

    public function test_bulk_ad_apis_require_authentication()
    {
        $this->postJson('/api/v1/customer/ads/bulk', ['ads' => []])->assertStatus(401);
        $this->postJson('/api/v1/customer/service/bulk', ['ads' => []])->assertStatus(401);
        $this->postJson('/api/v1/provider/add_services', ['ads' => []])->assertStatus(401);
        $this->postJson('/api/v1/admin/service/bulk', ['ads' => []])->assertStatus(401);
    }

    public function test_super_admin_can_open_bulk_ads_page()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)
            ->get('/admin/service/bulk')
            ->assertOk()
            ->assertSee('Excel import', false)
            ->assertSee('Quick add', false)
            ->assertSee('admin/discount/create', false)
            ->assertSee('admin/coupon/create', false)
            ->assertSee('admin/campaign/create', false)
            ->assertSee('data-remove-row', false);

        $this->actingAs($admin)
            ->get('/admin/service/bulk/template')
            ->assertOk();
    }

    public function test_admin_can_post_bulk_ads_for_a_customer()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        $subCategory = DB::table('categories')->where('position', 2)->where('is_active', 1)->whereNotNull('parent_id')->first();
        $zone = DB::table('zones')->first();
        if (!$admin || !$subCategory || !$zone) {
            $this->markTestSkipped('Admin, sub category or zone is missing.');
        }

        $customer = $this->makeCustomer();
        $created = [];
        try {
            $response = $this->actingAs($admin)->post('/admin/service/bulk', [
                'user_id' => $customer->id,
                'ads' => [
                    [
                        'name' => 'Bulk Test Bike ' . uniqid(),
                        'sub_category_id' => $subCategory->id,
                        'price' => 500,
                        'description' => 'First bulk ad',
                        'location' => 'Pune',
                    ],
                    [
                        'name' => 'Bulk Test Car ' . uniqid(),
                        'sub_category_id' => $subCategory->id,
                        'price' => 1500,
                        'description' => 'Second bulk ad',
                        'location' => 'Mumbai',
                    ],
                ],
            ]);
            $response->assertRedirect(route('admin.service.index'));

            $created = Service::query()->where('added_by', $customer->id)->latest()->take(2)->get();
            $this->assertGreaterThanOrEqual(2, $created->count());
            $this->assertEquals($customer->id, $created->first()->added_by);
        } finally {
            Service::query()->where('added_by', $customer->id)->forceDelete();
            $customer->forceDelete();
        }
    }

    public function test_posted_ad_service_creates_two_ads_for_one_user()
    {
        $subCategory = DB::table('categories')->where('position', 2)->where('is_active', 1)->whereNotNull('parent_id')->first();
        $zone = DB::table('zones')->first();
        if (!$subCategory || !$zone) {
            $this->markTestSkipped('Sub category or zone is missing.');
        }

        $customer = $this->makeCustomer();
        try {
            $result = app(PostedAdService::class)->createMany([
                [
                    'name' => 'Service Bulk One',
                    'sub_category_id' => $subCategory->id,
                    'price' => 100,
                    'description' => 'One',
                ],
                [
                    'name' => 'Service Bulk Two',
                    'sub_category' => $subCategory->name,
                    'price' => 200,
                    'description' => 'Two',
                ],
            ], $customer->id, false);

            $this->assertEquals(2, $result['created_count']);
            $this->assertEquals(0, $result['failed_count']);
            $this->assertEquals(2, Service::query()->where('added_by', $customer->id)->count());
        } finally {
            Service::query()->where('added_by', $customer->id)->forceDelete();
            $customer->forceDelete();
        }
    }

    private function makeCustomer(): User
    {
        $customer = new User();
        $customer->first_name = 'Bulk';
        $customer->last_name = 'Poster';
        $customer->email = 'bulk-' . uniqid() . '@mstoo.test';
        $customer->phone = '+1555' . random_int(1000000, 9999999);
        $customer->password = Hash::make('Password123');
        $customer->user_type = 'customer';
        $customer->is_active = 1;
        $customer->profile_image = 'def.png';
        $customer->save();
        return $customer;
    }
}
