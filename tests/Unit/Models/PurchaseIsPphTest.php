<?php

namespace Tests\Unit\Models;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PurchaseIsPphTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    /**
     * The purchase journal credits payables with the total AFTER the
     * withholding (PurchaseService::postPurchaseJournalEntry books Cr AP
     * $finalTotal and Cr PPh Payable separately), so the amount still owed
     * is net too. Both accessors stopped at the lines, so the withheld tax
     * was reported as money the tenant still owed its supplier, and a
     * purchase paid in full could never reach the Paid status because
     * updatePurchaseStatus() compared against the same gross figure.
     */
    #[Test]
    public function the_total_and_balance_are_net_of_the_withholding()
    {
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_rate' => 0,
            'tax_type' => 'none',
            'total_tax' => 0,
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 20,
            'total' => 980,
        ]);

        POItem::factory()->create([
            'po_id' => $purchase->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'price' => 1000,
            'total' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
        ]);

        $purchase->load('items');

        $this->assertEquals(
            980.0,
            $purchase->grand_total,
            'Grand total must match the 980 booked to payables, not the 1000 invoiced.'
        );

        $this->assertEquals(
            980.0,
            $purchase->balance,
            'Balance must not report the withheld 20 as still owed.'
        );

        $this->assertEquals(
            (float) $purchase->total,
            $purchase->grand_total,
            'A purchase may not hold two different totals at once.'
        );
    }

    /**
     * The status that follows a payment is decided from the same gross
     * figure, so settling a purchase in full still left it Partial.
     */
    #[Test]
    public function a_purchase_paid_in_full_after_withholding_is_paid()
    {
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_rate' => 0,
            'tax_type' => 'none',
            'total_tax' => 0,
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 20,
            'total' => 980,
            'status' => 'Unpaid',
        ]);

        POItem::factory()->create([
            'po_id' => $purchase->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'price' => 1000,
            'total' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
        ]);

        $purchase->payments()->create([
            'amount' => 980,
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $purchase->refresh();
        app(PurchaseService::class)->updatePurchaseStatus($purchase);

        $this->assertSame('Paid', $purchase->fresh()->status);
    }
}
