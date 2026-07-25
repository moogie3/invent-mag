<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sales;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;

class QuotationControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected Customer $customer;
    protected Tax $tax;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        Warehouse::factory()->create(['is_main' => true]);
        $this->customer = Customer::factory()->create();
        $this->tax = Tax::factory()->create([
            'rate' => 11,
            'type' => 'exclusive',
            'is_default' => true,
        ]);
    }

    private function createQuotation(array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'quotation_number' => 'QTN-' . now()->format('Ymd') . '-0001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_id' => $this->tax->id,
            'tax_type' => 'exclusive',
            'tax_rate' => 11,
            'tax_amount' => 110,
            'total' => 1110,
            'status' => 'draft',
            'items' => [
                ['product_id' => 1, 'name' => 'Test Product', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
            'notes' => null,
            'terms_conditions' => null,
        ], $overrides));
    }

    public function test_it_can_display_the_quotation_index_page()
    {
        $this->createQuotation();
        $this->createQuotation(['quotation_number' => 'QTN-' . now()->format('Ymd') . '-0002']);

        $response = $this->get(route('admin.quotations'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.index');
        $response->assertViewHas('quotations');
    }

    public function test_it_can_display_the_create_page()
    {
        $response = $this->get(route('admin.quotations.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-create');
        $response->assertViewHasAll(['customers', 'products', 'quotation']);
    }

    public function test_it_can_store_a_new_quotation()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'notes' => 'Test quotation',
            'terms_conditions' => 'Net 30',
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation created successfully.');

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'tax_type' => 'exclusive',
            'notes' => 'Test quotation',
            'terms_conditions' => 'Net 30',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => null,
            'quotation_date' => null,
            'products' => null,
        ]);

        $response->assertSessionHasErrors(['customer_id', 'quotation_date', 'products']);
    }

    public function test_store_validates_customer_exists()
    {
        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => 99999,
            'quotation_date' => now()->toDateString(),
            'products' => json_encode([['product_id' => 1, 'quantity' => 1, 'price' => 100]]),
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_it_can_display_the_show_page()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.show', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.show');
        $response->assertViewHas('quotation');
    }

    public function test_it_can_display_the_edit_page()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.edit', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-edit');
        $response->assertViewHasAll(['quotation', 'customers', 'products']);
    }

    public function test_it_can_update_a_quotation()
    {
        $quotation = $this->createQuotation();

        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(60)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Updated Product', 'quantity' => 5, 'price' => 200, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'notes' => 'Updated notes',
            'terms_conditions' => 'Updated terms',
        ];

        $response = $this->put(route('admin.quotations.update', $quotation->id), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation updated successfully.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'notes' => 'Updated notes',
            'terms_conditions' => 'Updated terms',
            'discount_value' => 10,
            'discount_amount' => 10,
        ]);
    }

    public function test_it_can_delete_a_quotation()
    {
        $quotation = $this->createQuotation();

        $response = $this->delete(route('admin.quotations.destroy', $quotation->id));

        $response->assertRedirect(route('admin.quotations'));
        $response->assertSessionHas('success', 'Quotation deleted successfully.');

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    }

    public function test_it_can_update_status()
    {
        $quotation = $this->createQuotation();

        $response = $this->post(route('admin.quotations.status', $quotation->id), [
            'status' => 'sent',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation status updated.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_update_status_validates_status_value()
    {
        $quotation = $this->createQuotation();

        $response = $this->post(route('admin.quotations.status', $quotation->id), [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_it_can_convert_to_sales_when_accepted()
    {
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'total' => 1110,
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation converted to sales order successfully.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
        ]);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'total' => 1110,
            'status' => 'pending',
        ]);
    }

    public function test_convert_to_sales_fails_when_not_accepted()
    {
        $quotation = $this->createQuotation(['status' => 'draft']);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));

        $response->assertSessionHas('error', 'Only accepted quotations can be converted to sales orders.');

        $this->assertDatabaseMissing('sales', [
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_it_can_bulk_delete_quotations()
    {
        $q1 = $this->createQuotation(['quotation_number' => 'QTN-' . now()->format('Ymd') . '-0010']);
        $q2 = $this->createQuotation(['quotation_number' => 'QTN-' . now()->format('Ymd') . '-0011']);

        $response = $this->post(route('admin.quotations.bulk-delete'), [
            'ids' => [$q1->id, $q2->id],
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('quotations', ['id' => $q1->id]);
        $this->assertDatabaseMissing('quotations', ['id' => $q2->id]);
    }

    public function test_bulk_delete_validates_ids_required()
    {
        $response = $this->post(route('admin.quotations.bulk-delete'), []);

        $response->assertSessionHasErrors(['ids']);
    }

    public function test_it_can_bulk_export_quotations_as_csv()
    {
        $this->createQuotation(['quotation_number' => 'QTN-20260101-0001']);

        $response = $this->post(route('admin.quotations.bulk-export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_bulk_export_validates_export_option()
    {
        $response = $this->post(route('admin.quotations.bulk-export'), [
            'export_option' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['export_option']);
    }

    public function test_it_can_get_product_details()
    {
        $product = Product::factory()->create(['selling_price' => 750]);

        $response = $this->get(route('admin.quotations.product.details', $product->id));

        $response->assertStatus(200);
        $response->assertJson([
            'price' => 750,
            'name' => $product->name,
        ]);
    }

    public function test_it_can_load_modal_view()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.modal-view', $quotation->id));

        $response->assertStatus(200);
    }

    public function test_it_can_print_quotation()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.print', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-print-pdf');
    }

    public function test_store_calculates_correct_totals_with_percentage_discount()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item A', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 10,
            'discount_total_type' => 'percentage',
            'tax_id' => $this->tax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();

        // Subtotal=1000, discount=10% => 100, after discount=900, tax=11% => 99, total=999
        $this->assertDatabaseHas('quotations', [
            'subtotal' => 1000,
            'discount_value' => 10,
            'discount_amount' => 100,
            'tax_rate' => 11,
            'total' => 999,
        ]);
    }

    public function test_store_calculates_correct_totals_with_fixed_discount()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item A', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 200,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();

        // Subtotal=1000, discount=200 fixed, after discount=800, tax=11% => 88, total=888
        $this->assertDatabaseHas('quotations', [
            'subtotal' => 1000,
            'discount_value' => 200,
            'discount_amount' => 200,
            'tax_rate' => 11,
            'total' => 888,
        ]);
    }

    public function test_show_returns_404_for_nonexistent_quotation()
    {
        $response = $this->get(route('admin.quotations.show', 99999));

        $response->assertStatus(404);
    }

    public function test_edit_returns_404_for_nonexistent_quotation()
    {
        $response = $this->get(route('admin.quotations.edit', 99999));

        $response->assertStatus(404);
    }

    public function test_delete_returns_404_for_nonexistent_quotation()
    {
        $response = $this->delete(route('admin.quotations.destroy', 99999));

        $response->assertStatus(404);
    }

    public function test_quotation_number_is_auto_generated()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item', 'quantity' => 1, 'price' => 100, 'discount' => 0, 'total' => 100],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
        ];

        $this->post(route('admin.quotations.store'), $data);

        $prefix = 'QTN-' . now()->format('Ymd');
        $this->assertDatabaseHas('quotations', [
            'quotation_number' => $prefix . '-0001',
        ]);

        // Create another - should increment
        $data['products'] = json_encode([
            ['product_id' => 1, 'name' => 'Item', 'quantity' => 1, 'price' => 200, 'discount' => 0, 'total' => 200],
        ]);
        $this->post(route('admin.quotations.store'), $data);

        $this->assertDatabaseHas('quotations', [
            'quotation_number' => $prefix . '-0002',
        ]);
    }

    public function tearDown(): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }
        parent::tearDown();
    }
}
