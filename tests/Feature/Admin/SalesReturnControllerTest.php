<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesItem;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;
use App\Models\Account;

class SalesReturnControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected Sales $sale;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the authenticated user has the necessary accounting settings
        $tenantId = app('currentTenant')->id;
        $this->user->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-' . $tenantId)->first()->id,
            'accounts_receivable_account_id' => Account::where('code', '1130-' . $tenantId)->first()->id,
            'sales_revenue_account_id' => Account::where('code', '4100-' . $tenantId)->first()->id,
            'sales_returns_account_id' => Account::where('code', '4200-' . $tenantId)->first()->id ?? 1, // Fallback if seeder differs
            'cost_of_goods_sold_account_id' => Account::where('code', '5200-' . $tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-' . $tenantId)->first()->id,
        ];
        $this->user->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->product = Product::factory()->withStock(10)->create();
        $customer = Customer::factory()->create();
        
        $this->sale = Sales::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
        ]);
        
        // Ensure sales item created and stock deducted (simulated)
        SalesItem::factory()->create([
            'sales_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'customer_price' => 100,
            'total' => 500
        ]);
        
        // Ensure pivot reflects post-sale state (10 - 5 = 5)
        // Note: Factory withStock(10) sets initial stock.
        // We simulate the sale already happened.
    }

    public function test_it_can_display_the_sales_return_index_page()
    {
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 200,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 300,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);

        $response = $this->get(route('admin.sales-returns.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.index');
        $response->assertViewHas('returns');
    }

    public function test_it_can_display_the_create_page()
    {
        $response = $this->get(route('admin.sales-returns.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.create');
        $response->assertViewHas('sales');
    }

    public function test_it_can_store_a_new_sales_return()
    {
        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 1, 'price' => 100]
            ]),
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Damaged goods', // Added reason for the return
        ];

        $response = $this->post(route('admin.sales-returns.store'), $returnData);

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return created successfully.');
        $this->assertDatabaseHas('sales_returns', ['sales_id' => $this->sale->id]);
    }

    public function test_it_can_show_a_sales_return()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturnItem::create([ // Create a SalesReturnItem
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $response = $this->get(route('admin.sales-returns.show', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.show');
        $response->assertViewHas('salesReturn', $salesReturn);
    }

    public function test_it_can_display_the_edit_page()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
        ]);

        $response = $this->get(route('admin.sales-returns.edit', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.edit');
        $response->assertViewHas('salesReturn', $salesReturn);
    }

    public function test_it_can_update_a_sales_return()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $updateData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 2, 'price' => 150]
            ]),
            'total_amount' => 300,
            'status' => 'Pending',
            'reason' => 'Customer changed mind', // Added reason for the return update
        ];

        $response = $this->put(route('admin.sales-returns.update', $salesReturn->id), $updateData);

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return updated successfully.');
        $this->assertDatabaseHas('sales_returns', [
            'id' => $salesReturn->id,
            'status' => 'Pending'
        ]);
    }

    public function test_it_can_delete_a_sales_return()
    {
        $salesReturn = SalesReturn::factory()->create();

        $response = $this->delete(route('admin.sales-returns.destroy', $salesReturn->id));

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return deleted successfully.');
        $this->assertDatabaseMissing('sales_returns', ['id' => $salesReturn->id]);
    }

    public function test_it_can_get_sales_items()
    {
        $response = $this->get(route('admin.sales-returns.items', $this->sale->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_it_can_bulk_delete_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create();
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-delete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('sales_returns', ['id' => $id]);
        }
    }

    public function test_it_can_bulk_complete_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-complete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'Completed']);
        }
    }

    public function test_it_can_bulk_cancel_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-cancel'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'Canceled']);
        }
    }

    public function test_it_can_show_modal_view()
    {
        $salesReturn = SalesReturn::factory()->create(['sales_id' => $this->sale->id]);

        $response = $this->get(route('admin.sales-returns.modal-view', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.sales.srmodals-view');
        $response->assertViewHas('salesReturn', $salesReturn);
    }
}
