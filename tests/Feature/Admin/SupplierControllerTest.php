<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        Warehouse::factory()->create(['is_main' => true]);
    }

    public function test_it_can_display_the_supplier_index_page()
    {
        Supplier::factory()->count(5)->create();

        $response = $this->get(route('admin.supplier'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.supplier.index');
        $response->assertViewHas('suppliers');
    }

    public function test_get_metrics_returns_json()
    {
        $expectedMetrics = [
            'totalSuppliers' => 10,
            'activeSuppliers' => 8,
            'newSuppliersThisMonth' => 2,
        ];

        // Mock the SupplierService
        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('getSupplierMetrics')
                    ->once()
                    ->andReturn($expectedMetrics);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->get(route('admin.supplier.metrics'));

        $response->assertStatus(200);
        $response->assertJson($expectedMetrics);
    }

    public function test_it_can_store_a_new_supplier()
    {
        $supplierData = [
            'code' => 'SUP001',
            'name' => 'Acme Corp',
            'address' => '123 Industrial Rd',
            'phone_number' => '555-1111',
            'location' => 'IN',
            'payment_terms' => 'Net 30',
            'email' => 'contact@acmecorp.com',
            'image' => UploadedFile::fake()->image('logo.jpg'),
        ];

        $response = $this->post(route('admin.supplier.store'), $supplierData);

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier created');

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP001',
            'name' => 'Acme Corp',
        ]);

        $supplier = Supplier::where('code', 'SUP001')->first();
        $this->assertNotNull($supplier->getRawOriginal('image'));
    }

    public function test_store_supplier_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'code' => '', // Required
            'name' => '', // Required
            'address' => '', // Required
            'phone_number' => '', // Required
            'location' => 'INVALID', // Not in: IN, OUT
            'payment_terms' => '', // Required
            'email' => 'not-an-email', // Invalid email
            'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'), // Invalid file type
        ];

        $response = $this->post(route('admin.supplier.store'), $invalidData);

        $response->assertSessionHasErrors(['code', 'name', 'address', 'phone_number', 'location', 'payment_terms', 'email', 'image']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_store_supplier_handles_service_level_error()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $supplierData = [
            'code' => 'SUP002',
            'name' => 'Beta Corp',
            'address' => '456 Beta St',
            'phone_number' => '555-2222',
            'location' => 'IN',
            'payment_terms' => 'Net 30',
        ];
        $errorMessage = 'Service creation failed.';

        // Mock the SupplierService to return a failure
        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('createSupplier')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $mockService->shouldReceive('getSupplierIndexData') // Add expectation for index method call
                    ->andReturn([
                        'suppliers' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                        'entries' => 10,
                        'totalsupplier' => 0,
                        'inCount' => 0,
                        'outCount' => 0,
                    ]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $this->get(route('admin.supplier')); // Set previous URL for back() redirect
        $response = $this->post(route('admin.supplier.store'), $supplierData);

        $response->assertSessionHasErrors(['name' => $errorMessage]);
        $response->assertRedirect(route('admin.supplier'));
    }

    public function test_it_can_update_a_supplier()
    {
        $supplier = Supplier::factory()->create();

        $updateData = [
            'code' => $supplier->code, // Code is required, keep original
            'name' => 'Updated Corp',
            'address' => '456 New St',
            'phone_number' => '555-2222',
            'location' => 'OUT',
            'payment_terms' => 'Net 60',
            'email' => 'updated@acmecorp.com',
        ];

        $response = $this->put(route('admin.supplier.update', $supplier->id), $updateData);

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier updated');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Corp',
            'email' => 'updated@acmecorp.com',
        ]);
    }

    public function test_update_supplier_with_invalid_data_returns_validation_errors()
    {
        $supplier = Supplier::factory()->create();

        $invalidUpdateData = [
            'code' => '', // Required
            'name' => '', // Required
            'address' => '', // Required
            'phone_number' => '', // Required
            'location' => 'INVALID', // Not in: IN, OUT
            'payment_terms' => '', // Required
            'email' => 'not-an-email', // Invalid email
            'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'), // Invalid file type
        ];

        $response = $this->put(route('admin.supplier.update', $supplier->id), $invalidUpdateData);

        $response->assertSessionHasErrors(['code', 'name', 'address', 'phone_number', 'location', 'payment_terms', 'email', 'image']);
        $response->assertStatus(302); // Redirect back on validation error
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => $supplier->name, // Should not be updated
        ]);
    }

    public function test_update_supplier_handles_service_level_exception()
    {
        $supplier = Supplier::factory()->create();
        $updateData = [
            'code' => $supplier->code,
            'name' => 'Updated Corp',
            'address' => '456 New St',
            'phone_number' => '555-2222',
            'location' => 'OUT',
            'payment_terms' => 'Net 60',
            'email' => 'updated@acmecorp.com',
        ];
        $errorMessage = 'Service update failed.';

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('updateSupplier')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->put(route('admin.supplier.update', $supplier->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('error', $errorMessage);
    }

    public function test_it_can_delete_a_supplier()
    {
        $supplier = Supplier::factory()->create();

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('deleteSupplier')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->delete(route('admin.supplier.destroy', $supplier->id));

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier deleted');
    }

    public function test_delete_supplier_handles_service_level_exception()
    {
        $supplier = Supplier::factory()->create();
        $errorMessage = 'Service deletion failed.';

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('deleteSupplier')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->delete(route('admin.supplier.destroy', $supplier->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', $errorMessage);
    }

    public function test_it_can_store_a_new_supplier_via_ajax()
    {
        $supplierData = [
            'code' => 'SUP001',
            'name' => 'Acme Corp',
            'address' => '123 Industrial Rd',
            'phone_number' => '555-1111',
            'location' => 'IN',
            'payment_terms' => 'Net 30',
        ];

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('createSupplier')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.supplier.store'), $supplierData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Supplier created successfully.']);
    }

    public function test_store_supplier_handles_service_level_error_via_ajax()
    {
        $supplierData = [
            'code' => 'SUP002',
            'name' => 'Beta Corp',
            'address' => '456 Beta St',
            'phone_number' => '555-2222',
            'location' => 'IN',
            'payment_terms' => 'Net 30',
        ];
        $errorMessage = 'AJAX creation failed.';

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('createSupplier')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.supplier.store'), $supplierData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['name' => [$errorMessage]]
            ]);
    }

    public function test_it_can_update_a_supplier_via_ajax()
    {
        $supplier = Supplier::factory()->create();
        $updateData = [
            'code' => $supplier->code,
            'name' => 'Updated AJAX Corp',
            'address' => '789 AJAX St',
            'phone_number' => '555-3333',
            'location' => 'OUT',
            'payment_terms' => 'Net 15',
        ];

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('updateSupplier')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.supplier.update', $supplier->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Supplier updated successfully.']);
    }

    public function test_update_supplier_handles_service_level_error_via_ajax()
    {
        $supplier = Supplier::factory()->create();
        $updateData = [
            'code' => $supplier->code,
            'name' => 'Updated AJAX Corp',
            'address' => '789 AJAX St',
            'phone_number' => '555-3333',
            'location' => 'OUT',
            'payment_terms' => 'Net 15',
        ];
        $errorMessage = 'AJAX update failed.';

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('updateSupplier')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.supplier.update', $supplier->id), $updateData);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }

    public function test_it_can_delete_a_supplier_via_ajax()
    {
        $supplier = Supplier::factory()->create();

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('deleteSupplier')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.supplier.destroy', $supplier->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Supplier deleted successfully.']);
    }

    public function test_delete_supplier_handles_service_level_error_via_ajax()
    {
        $supplier = Supplier::factory()->create();
        $errorMessage = 'AJAX deletion failed.';

        $mockService = \Mockery::mock(\App\Services\SupplierService::class);
        $mockService->shouldReceive('deleteSupplier')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\SupplierService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.supplier.destroy', $supplier->id));

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }
}
