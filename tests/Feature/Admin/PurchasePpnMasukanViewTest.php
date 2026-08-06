<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use Database\Factories\POItemFactory;
use Database\Factories\PurchaseFactory;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PurchasePpnMasukanViewTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Supplier $supplier;

    protected Product $product;

    protected Warehouse $warehouse;

    protected Tax $ppnTax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Indonesian tenant with the PPN feature enabled (via Professional plan features on the plan-less default)
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $tenantId = app('currentTenant')->id;
        $this->tenant->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-'.$tenantId)->first()->id,
            'accounts_payable_account_id' => Account::where('code', '2110-'.$tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-'.$tenantId)->first()->id,
            'ppn_masukan_account_id' => Account::where('code', '1160-'.$tenantId)->first()->id,
        ];
        $this->tenant->save();
        $this->actingAs($this->user);

        $this->ppnTax = Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
            'is_pph' => false,
        ]);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_create_page_renders_ppn_masukan_block(): void
    {
        $response = $this->get(route('admin.po.create'));

        $response->assertOk();
        $response->assertViewIs('admin.po.purchase-create');
        $response->assertSee('ppnMasukanRow');
        $response->assertSee('ppn_enabled');
        $response->assertSee('data-is-ppn="1"', false);
    }

    public function test_edit_page_renders_ppn_masukan_block(): void
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
            'tax_id' => $this->ppnTax->id,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'ppn_enabled' => true,
            'ppn_rate' => 11,
            'ppn_amount' => 220,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.po.edit', $purchase->id));

        $response->assertOk();
        $response->assertViewIs('admin.po.purchase-edit');
        $response->assertSee('ppnMasukanRow');
        $response->assertSee('ppn_enabled');
    }

    public function test_show_page_renders_ppn_masukan_row(): void
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
            'tax_id' => $this->ppnTax->id,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'total_tax' => 220,
            'ppn_enabled' => true,
            'ppn_rate' => 11,
            'ppn_amount' => 220,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.po.show', $purchase->id));

        $response->assertOk();
        $response->assertViewIs('admin.po.show');
        $response->assertSee('ppn-masukan-row');
        $response->assertSee('PPN Masukan');
    }

    public function test_non_ppn_purchase_hides_ppn_masukan_row_on_show(): void
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
            'ppn_enabled' => false,
            'ppn_rate' => null,
            'ppn_amount' => 0,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.po.show', $purchase->id));

        $response->assertOk();
        $response->assertDontSee('ppn-masukan-row');
    }
}
