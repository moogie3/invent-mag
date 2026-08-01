<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SalesHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PphHelperTest extends TestCase
{
    #[Test]
    public function it_calculates_pph_amount_for_exclusive_tax()
    {
        // PPh 23 at 2% on taxable amount of 10,000,000 = 200,000
        $result = SalesHelper::calculatePphAmount(10000000, 2);
        $this->assertEquals(200000.0, $result);
    }

    #[Test]
    public function it_calculates_pph_amount_with_zero_rate()
    {
        $result = SalesHelper::calculatePphAmount(10000000, 0);
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function it_calculates_pph_amount_with_zero_amount()
    {
        $result = SalesHelper::calculatePphAmount(0, 2);
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function it_calculates_pph_amount_with_negative_amount()
    {
        $result = SalesHelper::calculatePphAmount(-100, 2);
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function it_calculates_pph_amount_rounding()
    {
        // 100 * 2% = 2.00 (exact)
        $result = SalesHelper::calculatePphAmount(100, 2);
        $this->assertEquals(2.0, $result);

        // 150 * 2% = 3.00 (exact)
        $result = SalesHelper::calculatePphAmount(150, 2);
        $this->assertEquals(3.0, $result);
    }

    #[Test]
    public function it_subtracts_pph_from_invoice_summary()
    {
        // Subtotal 1000, tax 10%, PPh 2%
        // Taxable = 1000, Tax = 100, PPh = 20
        // Grand total = 1000 + 100 - 20 = 1080
        $items = collect([
            (object) ['customer_price' => 1000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 10, 'exclusive', 2);
        $this->assertEquals(20.0, $summary['pphAmount']);
        $this->assertEquals(1080.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_does_not_subtract_pph_when_rate_is_zero()
    {
        $items = collect([
            (object) ['customer_price' => 1000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 10, 'exclusive', 0);
        $this->assertEquals(0.0, $summary['pphAmount']);
        $this->assertEquals(1100.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_pph_is_always_exclusive_regardless_of_tax_type()
    {
        // Even with inclusive tax, PPh is calculated on taxable amount (before tax extraction)
        // Inclusive 10% on 1100 => taxAmount = 10, finalTotal before PPh = 1100
        // PPh 2% on 1100 = 22
        // finalTotal = 1100 - 22 = 1078
        $items = collect([
            (object) ['customer_price' => 1100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 10, 'inclusive', 2);
        $this->assertEquals(22.0, $summary['pphAmount']);
        $this->assertEquals(1078.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_pph_reduces_final_total_but_never_below_zero()
    {
        // Edge case: PPh > (taxableAmount + taxAmount)
        $items = collect([
            (object) ['customer_price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        // PPh 90% of 100 = 90, with no regular tax
        // finalTotal = max(0, 100 - 90) = 10
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 0, 'exclusive', 90);
        $this->assertEquals(90.0, $summary['pphAmount']);
        $this->assertEquals(10.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_pph_with_order_discount()
    {
        // Subtotal 1000, order discount 100 fixed, PPh 2%
        // Taxable = 1000 - 100 = 900
        // PPh = 900 * 2% = 18
        // finalTotal = 900 - 18 = 882
        $items = collect([
            (object) ['customer_price' => 1000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 100, 'fixed', 0, 'exclusive', 2);
        $this->assertEquals(18.0, $summary['pphAmount']);
        $this->assertEquals(882.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_pph_with_percentage_order_discount()
    {
        // Two items: subtotal 2000, order discount 10%, PPh 2%
        // Taxable = 2000 - 200 = 1800
        // PPh = 1800 * 2% = 36
        // finalTotal = 1800 - 36 = 1764
        $items = collect([
            (object) ['customer_price' => 1000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
            (object) ['customer_price' => 1000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 10, 'percentage', 0, 'exclusive', 2);
        $this->assertEquals(36.0, $summary['pphAmount']);
        $this->assertEquals(1764.0, $summary['finalTotal']);
    }

    #[Test]
    public function it_pph_with_regular_tax_and_order_discount()
    {
        // Subtotal 5000, order discount 500 fixed, PPN 11%, PPh 2%
        // Taxable = 5000 - 500 = 4500
        // PPN = 4500 * 11% = 495
        // PPh = 4500 * 2% = 90
        // finalTotal = 4500 + 495 - 90 = 4905
        $items = collect([
            (object) ['customer_price' => 5000, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $summary = SalesHelper::calculateInvoiceSummary($items, 500, 'fixed', 11, 'exclusive', 2);
        $this->assertEquals(90.0, $summary['pphAmount']);
        $this->assertEquals(495.0, $summary['taxAmount']);
        $this->assertEquals(4905.0, $summary['finalTotal']);
    }
}
