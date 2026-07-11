<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PurchaseHelper;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseHelperTest extends TestCase
{
    // Test cases for calculateTotal
    #[Test]
    public function it_calculates_total_with_no_discount()
    {
        $this->assertEquals(100.0, PurchaseHelper::calculateTotal(10.0, 10, 0, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_fixed_discount()
    {
        $this->assertEquals(80.0, PurchaseHelper::calculateTotal(10.0, 10, 2, 'fixed')); // (10-2)*10
    }

    #[Test]
    public function it_calculates_total_with_percentage_discount()
    {
        $this->assertEquals(90.0, PurchaseHelper::calculateTotal(10.0, 10, 10, 'percentage')); // (10 - 1)*10
    }

    #[Test]
    public function it_calculates_total_with_zero_quantity()
    {
        $this->assertEquals(0.0, PurchaseHelper::calculateTotal(10.0, 0, 2, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_zero_price()
    {
        $this->assertEquals(0.0, PurchaseHelper::calculateTotal(0.0, 10, 0, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_discount_exceeding_price()
    {
        $this->assertEquals(-50.0, PurchaseHelper::calculateTotal(5.0, 10, 10, 'fixed'));
    }

    // Test cases for calculateDiscountPerUnit
    #[Test]
    public function it_calculates_fixed_discount_per_unit()
    {
        $this->assertEquals(2.0, PurchaseHelper::calculateDiscountPerUnit(10.0, 2, 'fixed'));
    }

    #[Test]
    public function it_calculates_percentage_discount_per_unit()
    {
        $this->assertEquals(1.0, PurchaseHelper::calculateDiscountPerUnit(10.0, 10, 'percentage'));
    }

    #[Test]
    public function it_calculates_zero_discount_per_unit_for_zero_price()
    {
        $this->assertEquals(0.0, PurchaseHelper::calculateDiscountPerUnit(0.0, 10, 'percentage'));
    }

    // Test cases for calculateDiscount
    #[Test]
    public function it_calculates_fixed_order_discount()
    {
        $this->assertEquals(50.0, PurchaseHelper::calculateDiscount(200.0, 50, 'fixed'));
    }

    #[Test]
    public function it_calculates_percentage_order_discount()
    {
        $this->assertEquals(20.0, PurchaseHelper::calculateDiscount(200.0, 10, 'percentage'));
    }

    // ---------------------------------------------------------------------------
    // Tax-amount type tests — exclusive, inclusive, none
    // ---------------------------------------------------------------------------

    #[Test]
    public function it_calculates_exclusive_tax_amount()
    {
        // Exclusive: tax is added ON TOP — 10% of 100 = 10
        $this->assertEquals(10.0, PurchaseHelper::calculateTaxAmount(100.0, 10, 'exclusive'));
    }

    #[Test]
    public function it_calculates_inclusive_tax_amount()
    {
        // Inclusive: embedded tax extract = 110 - 110/1.10 = 10
        $this->assertEquals(10.0, PurchaseHelper::calculateTaxAmount(110.0, 10, 'inclusive'));
    }

    #[Test]
    public function it_calculates_none_tax_amount_as_zero()
    {
        // 'none' always returns 0 regardless of rate
        $this->assertEquals(0.0, PurchaseHelper::calculateTaxAmount(100.0, 15, 'none'));
    }

    #[Test]
    public function it_calculates_zero_order_discount_for_zero_subtotal()
    {
        $this->assertEquals(0.0, PurchaseHelper::calculateDiscount(0.0, 10, 'percentage'));
    }

    // Test cases for calculateInvoiceSummary
    #[Test]
    public function it_calculates_invoice_summary_for_empty_items()
    {
        $summary = PurchaseHelper::calculateInvoiceSummary([], 0, 'fixed');
        $this->assertEquals([
            'subtotal'             => 0.0,
            'itemCount'            => 0,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 0.0,
            'finalTotal'           => 0.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_single_item_no_order_discount()
    {
        $items = [
            (object)['price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']
        ];
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 0, 'fixed');
        $this->assertEquals([
            'subtotal'             => 100.0,
            'itemCount'            => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 0.0,
            'finalTotal'           => 100.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_multiple_items_and_order_discount()
    {
        $items = [
            (object)['price' => 100, 'quantity' => 1, 'discount' => 10, 'discount_type' => 'percentage'], // 90
            (object)['price' => 50, 'quantity' => 2, 'discount' => 5, 'discount_type' => 'fixed'],        // (50-5)*2 = 90
        ]; // Subtotal = 90 + 90 = 180
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 10, 'percentage'); // 10% of 180 = 18; no tax
        $this->assertEquals([
            'subtotal'             => 180.0,
            'itemCount'            => 2,
            'totalProductDiscount' => 10.0 + 10.0, // 10% of 100 = 10, fixed 5 * 2 = 10
            'orderDiscount'        => 18.0,
            'taxAmount'            => 0.0,          // taxRate defaults to 0
            'finalTotal'           => 162.0,        // 180 - 18 + 0 tax
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_array_items()
    {
        $items = [
            ['price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']
        ];
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 0, 'fixed');
        $this->assertEquals([
            'subtotal'             => 100.0,
            'itemCount'            => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 0.0,
            'finalTotal'           => 100.0,
        ], $summary);
    }

    // ---------------------------------------------------------------------------
    // Tax-type coverage — exclusive, inclusive, none
    // ---------------------------------------------------------------------------

    #[Test]
    public function it_calculates_invoice_summary_with_exclusive_tax()
    {
        // Subtotal 100, no discount, exclusive 10% tax => finalTotal = 100 + 10 = 110
        $items = [(object)['price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']];
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 0, 'fixed', 10, 'exclusive');
        $this->assertEquals([
            'subtotal'             => 100.0,
            'itemCount'            => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 10.0,
            'finalTotal'           => 110.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_inclusive_tax()
    {
        // Subtotal 110 (price already includes 10% tax).
        // Embedded tax portion = 110 - (110 / 1.10) = 110 - 100 = 10
        // finalTotal = 110 (no addition; tax is already inside)
        $items = [(object)['price' => 110, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']];
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 0, 'fixed', 10, 'inclusive');
        $this->assertEquals([
            'subtotal'             => 110.0,
            'itemCount'            => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 10.0,
            'finalTotal'           => 110.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_none_tax()
    {
        // taxType 'none' => taxAmount = 0, finalTotal = subtotal
        $items = [(object)['price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']];
        $summary = PurchaseHelper::calculateInvoiceSummary($items, 0, 'fixed', 15, 'none');
        $this->assertEquals([
            'subtotal'             => 100.0,
            'itemCount'            => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount'        => 0.0,
            'taxAmount'            => 0.0,
            'finalTotal'           => 100.0,
        ], $summary);
    }

    // Test cases for getStatusClass
    #[Test]
    public function it_returns_paid_status_class()
    {
        $this->assertEquals('badge bg-green-lt', PurchaseHelper::getStatusClass('Paid', Carbon::now()->addDays(5)));
    }

    #[Test]
    public function it_returns_overdue_status_class()
    {
        $this->assertEquals('badge bg-red-lt', PurchaseHelper::getStatusClass('Pending', Carbon::now()->subDays(1)));
    }

    #[Test]
    public function it_returns_due_soon_3_days_status_class()
    {
        $this->assertEquals('badge bg-orange-lt', PurchaseHelper::getStatusClass('Pending', Carbon::now()->addDays(3)));
    }

    #[Test]
    public function it_returns_due_soon_7_days_status_class()
    {
        $this->assertEquals('badge bg-yellow-lt', PurchaseHelper::getStatusClass('Pending', Carbon::now()->addDays(7)));
    }

    #[Test]
    public function it_returns_default_status_class()
    {
        $this->assertEquals('badge bg-blue-lt', PurchaseHelper::getStatusClass('Pending', Carbon::now()->addDays(8)));
    }

    // Test cases for getStatusText
    #[Test]
    public function it_returns_paid_status_text()
    {
        $this->assertEquals('<span class="h4"><i class="ti ti-check me-1 fs-4"></i> Paid</span>', PurchaseHelper::getStatusText('Paid', Carbon::now()->addDays(5)));
    }

    #[Test]
    public function it_returns_due_today_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1, 12, 0, 0));
        $this->assertEquals('<span class="h4"><i class="ti ti-alert-triangle me-1 fs-4"></i> Due Today</span>', PurchaseHelper::getStatusText('Pending', Carbon::create(2025, 1, 1, 10, 0, 0)));
        Carbon::setTestNow(); // Reset Carbon
    }

    #[Test]
    public function it_returns_due_in_days_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-calendar-event me-1 fs-4"></i> Due in 3 Days</span>', PurchaseHelper::getStatusText('Pending', Carbon::create(2025, 1, 4)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_due_in_1_week_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-calendar me-1 fs-4"></i> Due in 1 Week</span>', PurchaseHelper::getStatusText('Pending', Carbon::create(2025, 1, 8)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_overdue_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 5));
        $this->assertEquals('<span class="h4"><i class="ti ti-alert-circle me-1 fs-4"></i> Overdue</span>', PurchaseHelper::getStatusText('Pending', Carbon::create(2025, 1, 1)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_pending_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> Pending</span>', PurchaseHelper::getStatusText('Pending', Carbon::create(2025, 1, 10)));
        Carbon::setTestNow();
    }
}
