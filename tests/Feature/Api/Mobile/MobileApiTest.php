<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class MobileApiTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        Warehouse::factory()->create(['is_main' => true]);
        $this->product = Product::factory()->create(['barcode' => 'MOBILE-BARCODE-001']);

        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => Warehouse::first()->id,
                'tenant_id' => $this->product->tenant_id,
            ],
            ['quantity' => 10]
        );
    }

    protected function actingAsMobile()
    {
        Sanctum::actingAs($this->user);

        return $this;
    }

    // ------------------------------------------------------------------
    // Auth
    // ------------------------------------------------------------------

    public function test_mobile_routes_require_authentication(): void
    {
        // setupTenant() actingAs's a user — drop to guest so these requests
        // are genuinely unauthenticated.
        $this->actingAsGuest();

        $this->getJson('/api/v1/mobile/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/mobile/pos/products')->assertStatus(401);
        $this->getJson('/api/v1/mobile/products/search?q=a')->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function test_dashboard_returns_kpi_summary(): void
    {
        $response = $this->actingAsMobile()->getJson('/api/v1/mobile/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'key_metrics',
            'total_revenue',
            'monthly_sales',
            'unpaid_revenue',
            'unpaid_liability',
            'low_stock_count',
            'recent_sales',
            'recent_purchases',
            'collection_rate',
            'avg_due_days',
        ]);
    }

    public function test_dashboard_requires_access_dashboard_permission(): void
    {
        $this->user->removeRole('superuser');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/mobile/dashboard')->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // POS product search
    // ------------------------------------------------------------------

    public function test_pos_product_search_returns_catalog(): void
    {
        $response = $this->actingAsMobile()->getJson('/api/v1/mobile/pos/products');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('products'));
        $response->assertJsonStructure([
            'success',
            'has_more',
            'products' => [['id', 'code', 'name', 'selling_price', 'main_warehouse_stock']],
        ]);
    }

    public function test_pos_product_search_filters_by_query(): void
    {
        $named = Product::factory()->create(['name' => 'ZZUNIQUEPOSITEM']);

        $response = $this->actingAsMobile()
            ->getJson('/api/v1/mobile/pos/products?q=ZZUNIQUEPOSITEM');

        $response->assertStatus(200);
        $ids = collect($response->json('products'))->pluck('id');
        $this->assertTrue($ids->contains($named->id));
    }

    public function test_pos_product_search_requires_access_pos(): void
    {
        $this->user->removeRole('superuser');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/mobile/pos/products')->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // POS sale
    // ------------------------------------------------------------------

    public function test_pos_sale_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAsMobile()->postJson('/api/v1/mobile/pos/sales', [
            'transaction_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->selling_price,
                ],
            ],
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price,
            'change_amount' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('sale_id'));

        $this->assertDatabaseHas('sales', [
            'id' => $response->json('sale_id'),
            'customer_id' => $customer->id,
            'type' => Sales::TYPE_POS,
            'status' => 'Paid',
        ]);
    }

    public function test_pos_sale_rejects_insufficient_cash(): void
    {
        $response = $this->actingAsMobile()->postJson('/api/v1/mobile/pos/sales', [
            'transaction_date' => now()->format('Y-m-d'),
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 2,
                    'price' => $this->product->selling_price,
                ],
            ],
            'grand_total' => $this->product->selling_price * 2,
            'payment_method' => 'Cash',
            'amount_received' => 1,
            'change_amount' => 0,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }

    public function test_pos_sale_validation_requires_products(): void
    {
        $response = $this->actingAsMobile()->postJson('/api/v1/mobile/pos/sales', [
            'transaction_date' => now()->format('Y-m-d'),
            'grand_total' => 100,
            'payment_method' => 'Cash',
            'amount_received' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products']);
    }

    public function test_pos_sale_requires_access_pos(): void
    {
        $this->user->removeRole('superuser');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/mobile/pos/sales', [])->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // Stock validation
    // ------------------------------------------------------------------

    public function test_pos_validate_stock_returns_available(): void
    {
        $response = $this->actingAsMobile()->postJson('/api/v1/mobile/pos/validate-stock', [
            'products' => [
                ['id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('available', true);
        $this->assertEmpty($response->json('items'));
    }

    public function test_pos_validate_stock_flags_shortage(): void
    {
        $response = $this->actingAsMobile()->postJson('/api/v1/mobile/pos/validate-stock', [
            'products' => [
                ['id' => $this->product->id, 'quantity' => 999],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('available', false);
        $this->assertNotEmpty($response->json('items'));
    }

    // ------------------------------------------------------------------
    // Receipt
    // ------------------------------------------------------------------

    public function test_pos_receipt_returns_totals(): void
    {
        $sale = Sales::factory()->create([
            'type' => Sales::TYPE_POS,
            'user_id' => $this->user->id,
            'warehouse_id' => Warehouse::first()->id,
            'order_date' => now()->toDateString(),
            'total' => 10000,
            'status' => 'Paid',
            'payment_type' => 'Cash',
            'amount_received' => 10000,
        ]);

        $response = $this->actingAsMobile()->getJson("/api/v1/mobile/pos/receipt/{$sale->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'sale',
            'sub_total',
            'grand_total',
            'amount_received',
            'change',
        ]);
    }

    // ------------------------------------------------------------------
    // Product search + barcode
    // ------------------------------------------------------------------

    public function test_product_search_returns_matches(): void
    {
        $response = $this->actingAsMobile()
            ->getJson('/api/v1/mobile/products/search?q='.$this->product->code);

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($this->product->id));
    }

    public function test_product_search_requires_query(): void
    {
        $this->actingAsMobile()
            ->getJson('/api/v1/mobile/products/search')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_barcode_lookup_finds_product(): void
    {
        $response = $this->actingAsMobile()
            ->getJson('/api/v1/mobile/products/barcode?barcode=MOBILE-BARCODE-001');

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->product->id);
        $response->assertJsonPath('barcode', 'MOBILE-BARCODE-001');
    }

    public function test_barcode_lookup_returns_404_when_missing(): void
    {
        $this->actingAsMobile()
            ->getJson('/api/v1/mobile/products/barcode?barcode=NO-SUCH-CODE')
            ->assertStatus(404);
    }

    public function test_product_show_returns_detail(): void
    {
        $response = $this->actingAsMobile()
            ->getJson("/api/v1/mobile/products/{$this->product->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->product->id);
        $response->assertJsonStructure([
            'id', 'code', 'name', 'barcode', 'selling_price',
            'unit_symbol', 'main_warehouse_stock', 'total_stock',
        ]);
    }

    public function test_product_endpoints_require_view_products(): void
    {
        $this->user->removeRole('superuser');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/mobile/products/search?q=a')->assertStatus(403);
        $this->getJson('/api/v1/mobile/products/barcode?barcode=x')->assertStatus(403);
    }
}
