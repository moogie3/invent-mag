<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SalesHelper;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesHelperTest extends TestCase
{
    // Test cases for calculateTotal (reused from PurchaseHelperTest)
    #[Test]
    public function it_calculates_total_with_no_discount()
    {
        $this->assertEquals(100.0, SalesHelper::calculateTotal(10.0, 10, 0, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_fixed_discount()
    {
        $this->assertEquals(80.0, SalesHelper::calculateTotal(10.0, 10, 2, 'fixed')); // (10-2)*10
    }

    #[Test]
    public function it_calculates_total_with_percentage_discount()
    {
        $this->assertEquals(90.0, SalesHelper::calculateTotal(10.0, 10, 10, 'percentage')); // (10 - 1)*10
    }

    #[Test]
    public function it_calculates_total_with_zero_quantity()
    {
        $this->assertEquals(0.0, SalesHelper::calculateTotal(10.0, 0, 2, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_zero_price()
    {
        $this->assertEquals(0.0, SalesHelper::calculateTotal(0.0, 10, 0, 'fixed'));
    }

    #[Test]
    public function it_calculates_total_with_discount_exceeding_price()
    {
        $this->assertEquals(-50.0, SalesHelper::calculateTotal(5.0, 10, 10, 'fixed'));
    }

    // Test cases for calculateDiscountPerUnit (reused from PurchaseHelperTest)
    #[Test]
    public function it_calculates_fixed_discount_per_unit()
    {
        $this->assertEquals(2.0, SalesHelper::calculateDiscountPerUnit(10.0, 2, 'fixed'));
    }

    #[Test]
    public function it_calculates_percentage_discount_per_unit()
    {
        $this->assertEquals(1.0, SalesHelper::calculateDiscountPerUnit(10.0, 10, 'percentage'));
    }

    #[Test]
    public function it_calculates_zero_discount_per_unit_for_zero_price()
    {
        $this->assertEquals(0.0, SalesHelper::calculateDiscountPerUnit(0.0, 10, 'percentage'));
    }

    // Test cases for calculateDiscount (reused from PurchaseHelperTest)
    #[Test]
    public function it_calculates_fixed_order_discount()
    {
        $this->assertEquals(50.0, SalesHelper::calculateDiscount(200.0, 50, 'fixed'));
    }

    #[Test]
    public function it_calculates_percentage_order_discount()
    {
        $this->assertEquals(20.0, SalesHelper::calculateDiscount(200.0, 10, 'percentage'));
    }

    #[Test]
    public function it_calculates_zero_order_discount_for_zero_subtotal()
    {
        $this->assertEquals(0.0, SalesHelper::calculateDiscount(0.0, 10, 'percentage'));
    }

    // Test cases for calculateTaxAmount
    #[Test]
    public function it_calculates_tax_amount_correctly()
    {
        $this->assertEquals(10.0, SalesHelper::calculateTaxAmount(100.0, 10));
        $this->assertEquals(0.0, SalesHelper::calculateTaxAmount(0.0, 10));
        $this->assertEquals(0.0, SalesHelper::calculateTaxAmount(100.0, 0));
        $this->assertEquals(5.5, SalesHelper::calculateTaxAmount(50.0, 11));
    }

    #[Test]
    public function it_calculates_tax_amount_with_negative_amount()
    {
        $this->assertEquals(-10.0, SalesHelper::calculateTaxAmount(-100.0, 10));
    }

    // Test cases for calculateInvoiceSummary
    #[Test]
    public function it_calculates_invoice_summary_for_empty_items()
    {
        $summary = SalesHelper::calculateInvoiceSummary([], 0, 'fixed', 10);
        $this->assertEquals([
            'subtotal' => 0.0,
            'itemCount' => 0,
            'totalProductDiscount' => 0.0,
            'orderDiscount' => 0.0,
            'taxAmount' => 0.0,
            'finalTotal' => 0.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_single_item_no_order_discount_no_tax()
    {
        $items = [
            (object)['customer_price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']
        ];
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 0);
        $this->assertEquals([
            'subtotal' => 100.0,
            'itemCount' => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount' => 0.0,
            'taxAmount' => 0.0,
            'finalTotal' => 100.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_multiple_items_and_order_discount_and_tax()
    {
        $items = [
            (object)['customer_price' => 100, 'quantity' => 1, 'discount' => 10, 'discount_type' => 'percentage'], // 90
            (object)['customer_price' => 50, 'quantity' => 2, 'discount' => 5, 'discount_type' => 'fixed'], // (50-5)*2 = 90
        ]; // Subtotal after product discounts = 90 + 90 = 180
        // Subtotal before product discounts = 100*1 + 50*2 = 200
        $summary = SalesHelper::calculateInvoiceSummary($items, 10, 'percentage', 10); // Order discount 10% of 200 = 20
        // Taxable amount = 180 - 20 = 160
        // Tax amount = 10% of 160 = 16
        // Final total = 160 + 16 = 176
        $this->assertEquals([
            'subtotal' => 180.0,
            'itemCount' => 2,
            'totalProductDiscount' => 10.0 + 10.0, // 10% of 100 = 10, fixed 5 * 2 = 10
            'orderDiscount' => 20.0,
            'taxAmount' => 16.0,
            'finalTotal' => 176.0,
        ], $summary);
    }

    #[Test]
    public function it_calculates_invoice_summary_with_price_fallback()
    {
        $items = [
            (object)['price' => 100, 'quantity' => 1, 'discount' => 0, 'discount_type' => 'fixed']
        ];
        $summary = SalesHelper::calculateInvoiceSummary($items, 0, 'fixed', 0);
        $this->assertEquals([
            'subtotal' => 100.0,
            'itemCount' => 1,
            'totalProductDiscount' => 0.0,
            'orderDiscount' => 0.0,
            'taxAmount' => 0.0,
            'finalTotal' => 100.0,
        ], $summary);
    }

    // Test cases for getStatusClass (reused from PurchaseHelperTest)
    #[Test]
    public function it_returns_paid_status_class()
    {
        $this->assertEquals('badge bg-green-lt', SalesHelper::getStatusClass('Paid', Carbon::now()->addDays(5)));
    }

    #[Test]
    public function it_returns_overdue_status_class()
    {
        $this->assertEquals('badge bg-red-lt', SalesHelper::getStatusClass('Pending', Carbon::now()->subDays(1)));
    }

    #[Test]
    public function it_returns_due_soon_3_days_status_class()
    {
        $this->assertEquals('badge bg-orange-lt', SalesHelper::getStatusClass('Pending', Carbon::now()->addDays(3)));
    }

    #[Test]
    public function it_returns_due_soon_7_days_status_class()
    {
        $this->assertEquals('badge bg-yellow-lt', SalesHelper::getStatusClass('Pending', Carbon::now()->addDays(7)));
    }

    #[Test]
    public function it_returns_default_status_class()
    {
        $this->assertEquals('badge bg-blue-lt', SalesHelper::getStatusClass('Pending', Carbon::now()->addDays(8)));
    }

    // Test cases for getStatusText
    #[Test]
    public function it_returns_paid_status_text()
    {
        $this->assertEquals('<span class="h4"><i class="ti ti-check me-1 fs-4"></i> Paid</span>', SalesHelper::getStatusText('Paid', Carbon::now()->addDays(5)));
    }

    #[Test]
    public function it_returns_due_today_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1, 12, 0, 0));
        $this->assertEquals('<span class="h4"><i class="ti ti-alert-triangle me-1 fs-4"></i> Due Today</span>', SalesHelper::getStatusText('Pending', Carbon::create(2025, 1, 1, 10, 0, 0)));
        Carbon::setTestNow(); // Reset Carbon
    }

    #[Test]
    public function it_returns_due_in_days_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-calendar-event me-1 fs-4"></i> Due in 3 Days</span>', SalesHelper::getStatusText('Pending', Carbon::create(2025, 1, 4)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_due_in_1_week_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-calendar me-1 fs-4"></i> Due in 1 Week</span>', SalesHelper::getStatusText('Pending', Carbon::create(2025, 1, 8)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_overdue_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 5));
        $this->assertEquals('<span class="h4"><i class="ti ti-alert-circle me-1 fs-4"></i> Overdue</span>', SalesHelper::getStatusText('Pending', Carbon::create(2025, 1, 1)));
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_pending_status_text()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $this->assertEquals('<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> Pending</span>', SalesHelper::getStatusText('Pending', Carbon::create(2025, 1, 10)));
        Carbon::setTestNow();
    }
}
