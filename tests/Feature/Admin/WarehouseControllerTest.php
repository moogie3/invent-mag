<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseControllerTest extends TestCase
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
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $warehouse = Warehouse::factory()->create();
        $updateData = [
            'name' => 'Updated Warehouse Name',
            'address' => '456 Updated Ave',
            'description' => 'Updated description.',
            'is_main' => false,
        ];
        $errorMessage = 'Service update failed.';

        // Mock the WarehouseService to return a failure
        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('updateWarehouse')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
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
        $response = $this->put(route('admin.warehouse.update', $warehouse->id), $updateData);

        $response->assertSessionHasErrors(['is_main' => $errorMessage]); // Controller uses 'is_main' for service errors
        $response->assertRedirect(route('admin.warehouse'));
    }

    public function test_it_can_delete_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->delete(route('admin.warehouse.destroy', $warehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Warehouse deleted');

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    public function test_it_handles_service_level_error_on_destroy()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $warehouse = Warehouse::factory()->create();
        $errorMessage = 'Service deletion failed.';

        // Mock the WarehouseService to return a failure
        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('deleteWarehouse')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->delete(route('admin.warehouse.destroy', $warehouse->id));

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.warehouse'));
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]); // Should not be deleted
    }

    public function test_it_can_set_a_warehouse_as_main()
    {
        // Ensure no main warehouse exists initially for this test
        Warehouse::where('is_main', true)->update(['is_main' => false]);

        // Create a non-main warehouse
        $warehouse = Warehouse::factory()->create(['is_main' => false]);
        $this->assertFalse($warehouse->is_main);

        $response = $this->get(route('admin.warehouse.set-main', $warehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Main warehouse updated successfully');

        $this->assertEquals(1, $warehouse->fresh()->is_main);
        // Assert that no other warehouse is main
        $this->assertEquals(1, Warehouse::where('is_main', true)->count());
    }

    public function test_it_can_unset_a_main_warehouse()
    {
        // Ensure no main warehouse exists initially for this test
        Warehouse::where('is_main', true)->update(['is_main' => false]);

        // Create a main warehouse for this test
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->assertNotNull($mainWarehouse);
        $this->assertEquals(1, $mainWarehouse->is_main);

        $response = $this->get(route('admin.warehouse.unset-main', $mainWarehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Main warehouse status removed');

        $this->assertEquals(0, $mainWarehouse->fresh()->is_main);
    }

    public function test_it_handles_service_level_error_on_unset_main()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $errorMessage = 'Service unset main failed.';

        // Mock the WarehouseService to return a failure
        $mockService = \Mockery::mock(\App\Services\WarehouseService::class);
        $mockService->shouldReceive('unsetMainWarehouse')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\WarehouseService::class, $mockService);

        $response = $this->get(route('admin.warehouse.unset-main', $warehouse->id));

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.warehouse'));
        $this->assertEquals(1, $warehouse->fresh()->is_main); // Should still be main
    }
}
