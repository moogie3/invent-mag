<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ProductHelper;
use Carbon\Carbon;
use Tests\Unit\BaseUnitTestCase;

class ProductHelperTest extends BaseUnitTestCase
{
    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_null_for_null_expiry_date()
    {
        $this->assertEquals([null, null], ProductHelper::getExpiryClassAndText(null));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_expired_for_past_expiry_date()
    {
        $pastDate = Carbon::now()->subDays(5);
        $this->assertEquals(['badge bg-red text-white', 'Expired'], ProductHelper::getExpiryClassAndText($pastDate));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_expiring_soon_for_3_days_or_less()
    {
        $today = Carbon::now();
        $expiryDate = $today->copy()->addDays(3);
        $this->assertEquals(['badge bg-orange text-white', 'Expiring Soon (3d)'], ProductHelper::getExpiryClassAndText($expiryDate));

        $expiryDate = $today->copy()->addDays(1);
        $this->assertEquals(['badge bg-orange text-white', 'Expiring Soon (1d)'], ProductHelper::getExpiryClassAndText($expiryDate));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_expiring_soon_for_7_days_or_less_but_more_than_3_days()
    {
        $today = Carbon::now();
        $expiryDate = $today->copy()->addDays(7);
        $this->assertEquals(['badge bg-yellow text-white', 'Expiring Soon (7d)'], ProductHelper::getExpiryClassAndText($expiryDate));

        $expiryDate = $today->copy()->addDays(4);
        $this->assertEquals(['badge bg-yellow text-white', 'Expiring Soon (4d)'], ProductHelper::getExpiryClassAndText($expiryDate));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_expiring_in_for_30_days_or_less_but_more_than_7_days()
    {
        $today = Carbon::now();
        $expiryDate = $today->copy()->addDays(30);
        $this->assertEquals(['badge bg-blue text-white', 'Expiring in 30d'], ProductHelper::getExpiryClassAndText($expiryDate));

        $expiryDate = $today->copy()->addDays(8);
        $this->assertEquals(['badge bg-blue text-white', 'Expiring in 8d'], ProductHelper::getExpiryClassAndText($expiryDate));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_null_for_far_future_expiry_date()
    {
        $futureDate = Carbon::now()->addDays(31);
        $this->assertEquals([null, null], ProductHelper::getExpiryClassAndText($futureDate));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_handles_string_expiry_date()
    {
        $expiryDateString = Carbon::now()->addDays(2)->toDateString();
        $this->assertEquals(['badge bg-orange text-white', 'Expiring Soon (2d)'], ProductHelper::getExpiryClassAndText($expiryDateString));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_low_stock_for_stock_below_threshold()
    {
        $this->assertEquals(['badge bg-red text-white', 'Low Stock'], ProductHelper::getStockClassAndText(5, 10));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_low_stock_for_stock_at_threshold()
    {
        $this->assertEquals(['badge bg-red text-white', 'Low Stock'], ProductHelper::getStockClassAndText(10, 10));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_returns_in_stock_for_stock_above_threshold()
    {
        $this->assertEquals(['badge bg-green text-white', 'In Stock'], ProductHelper::getStockClassAndText(15, 10));
    }

    /**
     * @test
     * @group helpers
     * @group product-helper
     */
    public function it_handles_custom_stock_threshold()
    {
        $this->assertEquals(['badge bg-red text-white', 'Low Stock'], ProductHelper::getStockClassAndText(20, 25));
        $this->assertEquals(['badge bg-green text-white', 'In Stock'], ProductHelper::getStockClassAndText(30, 25));
    }
}
