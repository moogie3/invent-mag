<?php

namespace Tests\Unit\Services;

use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UnitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnitService $unitService;

    protected bool $seedDatabase = false; // Disable seeding for this test class

    protected function setUp(): void
    {
        parent::setUp();
        $this->unitService = new UnitService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(UnitService::class, $this->unitService);
    }

    #[Test]
    public function it_can_get_unit_index_data()
    {
        Unit::factory()->count(5)->create();

        $entries = 3;
        $result = $this->unitService->getUnitIndexData($entries);

        $this->assertArrayHasKey('units', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('totalunit', $result);

        $this->assertCount($entries, $result['units']);
        $this->assertEquals(5, $result['totalunit']);
        $this->assertEquals($entries, $result['entries']);
    }

    #[Test]
    public function it_can_create_a_unit_successfully()
    {
        $data = ['name' => 'Kilogram', 'short_name' => 'KG', 'allow_decimal' => true, 'symbol' => 'kg'];
        $result = $this->unitService->createUnit($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Unit created successfully.', $result['message']);
        $this->assertDatabaseHas('units', ['name' => 'Kilogram']);
    }

    #[Test]
    public function it_returns_error_if_unit_already_exists()
    {
        Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);

        $data = ['name' => 'Kilogram', 'symbol' => 'kg'];
        $result = $this->unitService->createUnit($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('This unit already exists.', $result['message']);
        $this->assertDatabaseCount('units', 1);
    }

    #[Test]
    public function it_can_update_a_unit_successfully()
    {
        $unit = Unit::create(['name' => 'Gram', 'symbol' => 'g']);
        $updatedData = ['name' => 'Kilogram', 'symbol' => 'kg'];

        $result = $this->unitService->updateUnit($unit, $updatedData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Unit updated successfully.', $result['message']);
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'Kilogram', 'symbol' => 'kg']);
    }

    #[Test]
    public function it_can_delete_a_unit_successfully()
    {
        $unit = Unit::create(['name' => 'Liter', 'short_name' => 'L', 'allow_decimal' => true, 'symbol' => 'l']);

        $result = $this->unitService->deleteUnit($unit);

        $this->assertTrue($result['success']);
        $this->assertEquals('Unit deleted successfully.', $result['message']);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }
}