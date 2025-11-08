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

    public function test_it_can_delete_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->delete(route('admin.warehouse.destroy', $warehouse->id));

        $response->assertRedirect(route('admin.warehouse'));
        $response->assertSessionHas('success', 'Warehouse deleted');

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
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
}
