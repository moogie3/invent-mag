<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;

class WarehouseControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    public function test_it_can_display_the_warehouse_index_page()
    {
        Warehouse::factory()->count(5)->create(['is_main' => false]); // Create non-main warehouses

        $response = $this->get(route('admin.warehouse'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.warehouse.index');
        $response->assertViewHas('wos');
    }

    public function test_it_can_store_a_new_warehouse()
    {
        $warehouseData = [
            'name' => 'New Warehouse',
            'address' => '123 Test St',
            'description' => 'A new test warehouse.',
            'is_main' => false,
        ];

        $response = $this->post(route('admin.warehouse.store'), $warehouseData);

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Warehouse created');

        $this->assertDatabaseHas('warehouses', [
            'name' => 'New Warehouse',
        ]);
    }

    public function test_it_cannot_store_a_warehouse_with_invalid_data()
    {
        $invalidWarehouseData = [
            'name' => '', // Required
            'address' => '', // Required
            'description' => '', // Required
        ];

        $response = $this->post(route('admin.warehouse.store'), $invalidWarehouseData);

        $response->assertSessionHasErrors(['name', 'address', 'description']);
        $response->assertStatus(302); // Redirect back on validation error
        $this->assertDatabaseMissing('warehouses', ['name' => '']);
    }

    public function test_it_handles_service_level_error_on_store()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling to see the actual exception if any

        $warehouseData = [
            'name' => 'New Warehouse',
            'address' => '123 Test St',
            'description' => 'A new test warehouse.',
            'is_main' => false,
        ];

        // Mock the WarehouseService to return a failure
        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('createWarehouse')
                    ->once()
                    ->andReturn(['success' => false, 'message' => 'Service creation failed.']);
        $mockService->shouldReceive('getWarehouseIndexData') // Add expectation for index method call
                    ->andReturn([
                        'wos' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                        'entries' => 10,
                        'totalwarehouse' => 0,
                        'mainWarehouse' => null,
                        'shopname' => 'Test Shop',
                        'address' => 'Test Address',
                    ]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $this->get(route('admin.warehouse')); // Set previous URL for back() redirect
        $response = $this->post(route('admin.warehouse.store'), $warehouseData);

        $response->assertSessionHasErrors(['name' => 'Service creation failed.']);
        $response->assertRedirect(route('admin.warehouse'));
    }

    public function test_it_can_update_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'name' => 'Updated Warehouse Name',
            'address' => '456 Updated Ave',
            'description' => 'Updated description.',
            'is_main' => false,
        ];

        $response = $this->put(route('admin.warehouse.update', $warehouse->id), $updateData);

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Warehouse updated');

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Updated Warehouse Name',
        ]);
    }

    public function test_it_cannot_update_a_warehouse_with_invalid_data()
    {
        $warehouse = Warehouse::factory()->create();

        $invalidUpdateData = [
            'name' => '', // Required
            'address' => '', // Required
            'description' => '', // Required
        ];

        $response = $this->put(route('admin.warehouse.update', $warehouse->id), $invalidUpdateData);

        $response->assertSessionHasErrors(['name', 'address', 'description']);
        $response->assertStatus(302); // Redirect back on validation error
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => $warehouse->name, // Should not be updated
        ]);
    }

    public function test_it_handles_service_level_error_on_update()
    {
        $warehouse = Warehouse::factory()->create();
        $updateData = [
            'name' => 'Updated Warehouse Name',
            'address' => '456 Updated Ave',
            'description' => 'Updated description.',
        ];
        $errorMessage = 'Service update failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('updateWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->put(route('admin.warehouse.update', $warehouse->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('error', $errorMessage);
    }

    public function test_it_can_delete_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('deleteWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->delete(route('admin.warehouse.destroy', $warehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Warehouse deleted');
    }

    public function test_it_handles_service_level_error_on_destroy()
    {
        $warehouse = Warehouse::factory()->create();
        $errorMessage = 'Service deletion failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('deleteWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->delete(route('admin.warehouse.destroy', $warehouse->id));

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.warehouse'));
    }

    public function test_it_can_set_a_warehouse_as_main()
    {
        Warehouse::where('is_main', true)->update(['is_main' => false]);
        $warehouse = Warehouse::factory()->create(['is_main' => false]);

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('setMainWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->get(route('admin.warehouse.set-main', $warehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Main warehouse updated successfully');
    }

    public function test_it_can_unset_a_main_warehouse()
    {
        Warehouse::where('is_main', true)->update(['is_main' => false]);
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('unsetMainWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->get(route('admin.warehouse.unset-main', $mainWarehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Main warehouse status removed');
    }

    public function test_it_handles_service_level_error_on_unset_main()
    {
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $errorMessage = 'Service unset main failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('unsetMainWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->get(route('admin.warehouse.unset-main', $warehouse->id));

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.warehouse'));
    }

    public function test_it_can_store_a_new_warehouse_via_ajax()
    {
        $warehouseData = [
            'name' => 'AJAX Warehouse',
            'address' => '123 AJAX St',
            'description' => 'A new AJAX warehouse.',
        ];

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('createWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.warehouse.store'), $warehouseData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Warehouse created successfully.']);
    }

    public function test_store_warehouse_handles_service_level_error_via_ajax()
    {
        $warehouseData = [
            'name' => 'AJAX Warehouse',
            'address' => '123 AJAX St',
            'description' => 'A new AJAX warehouse.',
        ];
        $errorMessage = 'AJAX creation failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('createWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.warehouse.store'), $warehouseData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['name' => [$errorMessage]]
            ]);
    }

    public function test_it_can_update_a_warehouse_via_ajax()
    {
        $warehouse = Warehouse::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Warehouse',
            'address' => '456 AJAX Ave',
            'description' => 'Updated AJAX description.',
        ];

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('updateWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.warehouse.update', $warehouse->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Warehouse updated successfully.']);
    }

    public function test_update_warehouse_handles_service_level_error_via_ajax()
    {
        $warehouse = Warehouse::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Warehouse',
            'address' => '456 AJAX Ave',
            'description' => 'Updated AJAX description.',
        ];
        $errorMessage = 'AJAX update failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('updateWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.warehouse.update', $warehouse->id), $updateData);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }

    public function test_it_can_delete_a_warehouse_via_ajax()
    {
        $warehouse = Warehouse::factory()->create();

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('deleteWarehouse')->once()->andReturn(['success' => true]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.warehouse.destroy', $warehouse->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Warehouse deleted successfully.']);
    }

    public function test_delete_warehouse_handles_service_level_error_via_ajax()
    {
        $warehouse = Warehouse::factory()->create();
        $errorMessage = 'AJAX deletion failed.';

        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('deleteWarehouse')->once()->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.warehouse.destroy', $warehouse->id));

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }
}
