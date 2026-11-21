<?php

namespace Tests\Feature\Admin;

use App\Helpers\CurrencyHelper;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PrintTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_pos_receipt_can_be_rendered()
    {
        // Create a product and a POS sale with one item
        $product = Product::factory()->create();
        $sale = Sales::factory()->create(['type' => Sales::TYPE_POS]);
        $salesItem = SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'customer_price' => 150,
            'total' => 300,
        ]);

        $sale->refresh();

        $response = $this->get(route('admin.pos.receipt', $sale->id));

        $response->assertOk();
        $response->assertSeeText(__('messages.pos_receipt_title'));
        $response->assertSeeText('300');
    }

    public function test_sales_invoice_can_be_rendered_for_printing()
    {
        $sale = Sales::factory()->create(['type' => Sales::TYPE_REGULAR]);

        $response = $this->get(route('admin.sales.show', $sale->id));

        $response->assertOk();
        $response->assertSeeText(__('messages.view_sales_invoice'));
        $response->assertSeeText($sale->customer->name);
    }

    public function test_purchase_order_can_be_rendered_for_printing()
    {
        $purchase = Purchase::factory()->create();

        $response = $this->get(route('admin.po.show', $purchase->id));

        $response->assertOk();
        $response->assertSeeText(__('messages.view_po_invoice'));
        $response->assertSeeText($purchase->supplier->name);
    }

    /**
     * The printed purchase order recomputed its summary from the lines
     * alone, dropping the tax rate, the tax type and the withholding the
     * invoice itself carries. A PO with VAT therefore printed a grand
     * total smaller than the one on the books, and the tenant's own copy
     * disagreed with the row it was printed from.
     */
    public function test_purchase_order_pdf_prints_the_total_the_order_holds()
    {
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'total_tax' => 22,
            'pph_enabled' => false,
            'pph_rate' => null,
            'pph_amount' => 0,
            'total' => 222,
        ]);

        POItem::factory()->create([
            'po_id' => $purchase->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
            'discount' => 0,
            'discount_type' => 'fixed',
        ]);

        $html = view('admin.po.print-pdf', [
            'pos' => Purchase::with(['supplier', 'items.product', 'payments', 'purchaseReturns.items'])
                ->findOrFail($purchase->id),
            'shopname' => null,
            'address' => null,
        ])->render();

        $this->assertSame(
            CurrencyHelper::formatWithPosition(222),
            $this->grandTotalRow($html),
            'The printed order must show the 222 the purchase holds, not the untaxed 200.'
        );
    }

    /**
     * Same omission on the sales side, in the direction that matters more:
     * an inclusive tax was read as exclusive and the withholding was never
     * subtracted, so the printed invoice asked for more than the invoice
     * it was generated from.
     */
    public function test_sales_invoice_pdf_prints_the_total_the_invoice_holds()
    {
        $sale = Sales::factory()->create([
            'tax_rate' => 11,
            'tax_type' => 'inclusive',
            'total_tax' => 110,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 22.20,
            'total' => 1087.80,
        ]);

        SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'customer_price' => 1110,
            'total' => 1110,
            'discount' => 0,
            'discount_type' => 'fixed',
        ]);

        $sale = Sales::with(['customer', 'salesItems.product', 'payments'])->findOrFail($sale->id);

        $this->assertEqualsWithDelta(1087.80, $sale->grand_total, 0.005);

        $html = view('admin.sales.print-pdf', [
            'sales' => $sale,
            'shopname' => null,
            'address' => null,
        ])->render();

        $this->assertSame(
            CurrencyHelper::formatWithPosition(1087.80),
            $this->grandTotalRow($html),
            'The printed invoice must show the 1087.80 the invoice holds, not 1232.10.'
        );
    }

    /**
     * The printed grand total on its own. Asserting on the whole document
     * lets a neighbouring row (the balance prints the same figure) satisfy
     * the check while the total itself is wrong.
     */
    private function grandTotalRow(string $html): string
    {
        preg_match('/Grand Total:<\/td>\s*<td>(.*?)<\/td>/s', $html, $matches);

        return trim($matches[1] ?? '');
    }

    public function test_product_index_page_can_be_rendered()
    {
        $response = $this->get(route('admin.product'));
        $response->assertOk();
        $response->assertSeeText(__('messages.product_title'));
    }

    public function test_supplier_index_page_can_be_rendered()
    {
        $response = $this->get(route('admin.supplier'));
        $response->assertOk();
        $response->assertSeeText(__('messages.supplier_title'));
    }

    public function test_customer_index_page_can_be_rendered()
    {
        $response = $this->get(route('admin.customer'));
        $response->assertOk();
        $response->assertSeeText(__('messages.customer_title'));
    }

    public function test_sales_index_page_can_be_rendered()
    {
        $response = $this->get(route('admin.sales'));
        $response->assertOk();
        $response->assertSeeText(__('messages.model_sales'));
    }

    public function test_purchase_index_page_can_be_rendered()
    {
        $response = $this->get(route('admin.po'));
        $response->assertOk();
        $response->assertSeeText(__('messages.purchase_order'));
    }
}
