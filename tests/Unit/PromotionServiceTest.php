<?php

namespace Tests\Unit;

use Modules\PromotionManagement\Services\PromotionService;
use PHPUnit\Framework\TestCase;

class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionService();
    }

    private function keeper(array $overrides = []): object
    {
        return (object) array_merge([
            'discount_amount_type' => 'percent',
            'discount_amount' => 10,
            'max_discount_amount' => 0,
            'min_purchase' => 0,
        ], $overrides);
    }

    public function test_valid_percentage_discount()
    {
        $this->assertEquals(100.0, $this->service->discountAmount($this->keeper(), 1000));
    }

    public function test_percentage_discount_is_capped_by_maximum()
    {
        $keeper = $this->keeper(['max_discount_amount' => 40]);
        $this->assertEquals(40.0, $this->service->discountAmount($keeper, 1000));
    }

    public function test_percentage_above_100_is_capped()
    {
        $keeper = $this->keeper(['discount_amount' => 150, 'max_discount_amount' => 0]);
        $this->assertEquals(200.0, $this->service->discountAmount($keeper, 200));
    }

    public function test_valid_fixed_discount()
    {
        $keeper = $this->keeper([
            'discount_amount_type' => 'amount',
            'discount_amount' => 75,
        ]);
        $this->assertEquals(75.0, $this->service->discountAmount($keeper, 200));
    }

    public function test_fixed_discount_cannot_exceed_purchase()
    {
        $keeper = $this->keeper([
            'discount_amount_type' => 'amount',
            'discount_amount' => 500,
        ]);
        $this->assertEquals(80.0, $this->service->discountAmount($keeper, 80));
    }

    public function test_minimum_order_requirement()
    {
        $keeper = $this->keeper(['min_purchase' => 500]);
        $this->assertEquals(0.0, $this->service->discountAmount($keeper, 100));
        $this->assertEquals(50.0, $this->service->discountAmount($keeper, 500));
    }

    public function test_null_keeper_returns_zero()
    {
        $this->assertEquals(0.0, $this->service->discountAmount(null, 1000));
    }

    public function test_wallet_bonus_uses_bonus_fields()
    {
        $bonus = (object) [
            'bonus_amount_type' => 'percent',
            'bonus_amount' => 20,
            'max_bonus_amount' => 50,
            'min_add_money_amount' => 100,
        ];
        $this->assertEquals(0.0, $this->service->discountAmount($bonus, 50));
        $this->assertEquals(50.0, $this->service->discountAmount($bonus, 400));
    }

    public function test_wallet_bonus_fixed_amount()
    {
        $bonus = (object) [
            'bonus_amount_type' => 'amount',
            'bonus_amount' => 25,
            'max_bonus_amount' => 25,
            'min_add_money_amount' => 200,
        ];
        $this->assertEquals(25.0, $this->service->discountAmount($bonus, 200));
    }

    public function test_minimum_can_be_skipped_for_coupon_line_items()
    {
        $keeper = $this->keeper(['min_purchase' => 500, 'discount_amount' => 10]);
        $this->assertEquals(0.0, $this->service->discountAmount($keeper, 100, true));
        $this->assertEquals(10.0, $this->service->discountAmount($keeper, 100, false));
    }

    public function test_frontend_manipulated_totals_are_ignored_by_engine()
    {
        $keeper = $this->keeper(['discount_amount_type' => 'amount', 'discount_amount' => 20]);
        $claimed = 9999;
        $this->assertNotEquals($claimed, $this->service->discountAmount($keeper, 100));
        $this->assertEquals(20.0, $this->service->discountAmount($keeper, 100));
    }
}
