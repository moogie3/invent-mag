<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PrintTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_pos_receipt_can_be_rendered()
    {
        // Create a product and a POS sale with one item
        $product = Product::factory()->create();
        $sale = Sales::factory()->create(['is_pos' => true]);
        $salesItem = \App\Models\SalesItem::factory()->create([
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
        $sale = Sales::factory()->create(['is_pos' => false]);

        $response = $this->get(route('admin.sales.view', $sale->id));

        $response->assertOk();
        $response->assertSeeText(__('messages.view_sales_invoice'));
        $response->assertSeeText($sale->customer->name);
    }

    public function test_purchase_order_can_be_rendered_for_printing()
    {
        $purchase = Purchase::factory()->create();

        $response = $this->get(route('admin.po.view', $purchase->id));

        $response->assertOk();
        $response->assertSeeText(__('messages.view_po_invoice'));
        $response->assertSeeText($purchase->supplier->name);
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
