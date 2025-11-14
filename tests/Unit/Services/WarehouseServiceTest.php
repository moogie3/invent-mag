<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;

class WarehouseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WarehouseService $warehouseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouseService = new WarehouseService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(WarehouseService::class, $this->warehouseService);
    }

    #[Test]
    public function it_can_get_warehouse_index_data()
    {
        Warehouse::factory()->count(5)->create();
        Warehouse::factory()->create(['is_main' => true]);
        User::factory()->create(['shopname' => 'Test Shop', 'address' => 'Test Address']);

        $entries = 3;
        $data = $this->warehouseService->getWarehouseIndexData($entries);

        $this->assertArrayHasKey('wos', $data);
        $this->assertArrayHasKey('entries', $data);
        $this->assertArrayHasKey('totalwarehouse', $data);
        $this->assertArrayHasKey('mainWarehouse', $data);
        $this->assertArrayHasKey('shopname', $data);
        $this->assertArrayHasKey('address', $data);

        $this->assertCount($entries, $data['wos']);
        $this->assertEquals(6, $data['totalwarehouse']);
        $this->assertNotNull($data['mainWarehouse']);
        $this->assertEquals('Test Shop', $data['shopname']);
    }

    #[Test]
    public function it_can_create_a_warehouse()
    {
        $data = ['name' => 'New Warehouse', 'address' => '123 Street', 'is_main' => false, 'description' => 'Some description'];
        $result = $this->warehouseService->createWarehouse($data);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('warehouses', ['name' => 'New Warehouse']);
    }

    #[Test]
    public function it_returns_error_if_warehouse_already_exists()
    {
        Warehouse::factory()->create(['name' => 'Existing Warehouse']);
        $data = ['name' => 'Existing Warehouse', 'address' => '123 Street', 'is_main' => false, 'description' => 'Some description'];
        $result = $this->warehouseService->createWarehouse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('This warehouse already exists.', $result['message']);
    }

    #[Test]
    public function it_returns_error_if_creating_second_main_warehouse()
    {
        Warehouse::factory()->create(['is_main' => true]);
        $data = ['name' => 'Second Main', 'address' => '456 Avenue', 'is_main' => true, 'description' => 'Some description'];
        $result = $this->warehouseService->createWarehouse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('There is already a main warehouse defined. Please unset the current main warehouse first.', $result['message']);
    }

    #[Test]
    public function it_can_update_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Old Name']);
        $data = ['name' => 'Updated Name', 'address' => 'New Address', 'description' => 'Updated description'];
        $result = $this->warehouseService->updateWarehouse($warehouse, $data);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function it_returns_error_if_updating_to_second_main_warehouse()
    {
        Warehouse::factory()->create(['is_main' => true]);
        $warehouse = Warehouse::factory()->create(['is_main' => false]);
        $data = ['is_main' => true, 'name' => $warehouse->name, 'address' => $warehouse->address, 'description' => $warehouse->description];
        $result = $this->warehouseService->updateWarehouse($warehouse, $data);

        $this->assertFalse($result['success']);
        $this->assertEquals('There is already a main warehouse defined. Please unset the current main warehouse first.', $result['message']);
    }

    #[Test]
    public function it_can_delete_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['is_main' => false]);
        $result = $this->warehouseService->deleteWarehouse($warehouse);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    #[Test]
    public function it_returns_error_if_deleting_main_warehouse()
    {
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        $result = $this->warehouseService->deleteWarehouse($mainWarehouse);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot delete the main warehouse. Please set another warehouse as main first.', $result['message']);
    }

    #[Test]
    public function it_can_set_a_warehouse_as_main()
    {
        $oldMainWarehouse = Warehouse::factory()->create(['is_main' => true, 'name' => 'Old Main']);
        $newMainWarehouse = Warehouse::factory()->create(['is_main' => false, 'name' => 'New Main']);

        $result = $this->warehouseService->setMainWarehouse($newMainWarehouse);

        $this->assertEquals(true, $result['success']);
        // Refresh models to get the latest state from the database
        $oldMainWarehouse->refresh();
        $newMainWarehouse->refresh();

        // Assert that the database state is correct
        $this->assertEquals(true, $newMainWarehouse->is_main);
        $this->assertEquals(false, $oldMainWarehouse->is_main);
    }

    #[Test]
    public function it_can_unset_a_main_warehouse()
    {
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        $result = $this->warehouseService->unsetMainWarehouse($mainWarehouse);

        $this->assertEquals(true, $result['success']);
        // Refresh model to get the latest state from the database
        $mainWarehouse->refresh();
        // Assert that the database state is correct
        $this->assertEquals(false, $mainWarehouse->is_main);
    }

    #[Test]
    public function it_returns_error_if_unsetting_non_main_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['is_main' => false]);
        $result = $this->warehouseService->unsetMainWarehouse($warehouse);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('This is not the main warehouse.', $result['message']);
    }
}
