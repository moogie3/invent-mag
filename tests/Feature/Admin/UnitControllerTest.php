<?php

namespace Tests\Feature\Admin;

use App\Models\Unit;
use App\Models\User;
use App\Services\UnitService;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\BaseFeatureTestCase;

class UnitControllerTest extends BaseFeatureTestCase
{
    use WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create an admin user and assign the 'superuser' role
        $this->admin = User::factory()->create();
        Role::findOrCreate('superuser', 'web');
        $this->admin->assignRole('superuser');
        $this->actingAs($this->admin);
    }

    public function test_index_displays_unit_settings_page()
    {
        $units = Unit::factory()->count(3)->create();
        $perPage = 10;
        $currentPage = 1;
        $total = $units->count();

        $paginatedUnits = new LengthAwarePaginator(
            $units->forPage($currentPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => route('admin.setting.unit')]
        );

        $unitData = [
            'units' => $paginatedUnits,
            'entries' => $perPage,
            'totalunit' => $total,
        ];

        // Mock the UnitService
        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unitData) {
                $mock->shouldReceive('getUnitIndexData')->once()->andReturn($unitData);
            })
        );

        $response = $this->get(route('admin.setting.unit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.unit.index');
        $response->assertViewHas('units', $unitData['units']);
        $response->assertViewHas('entries', $unitData['entries']);
    }

    public function test_store_unit_successfully()
    {
        $unitData = [
            'name' => 'Kilogram',
            'symbol' => 'kg',
        ];

        // Mock the UnitService
        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unitData) {
                $mock->shouldReceive('createUnit')->once()->with($unitData)->andReturn(['success' => true]);
            })
        );

        $response = $this->post(route('admin.setting.unit.store'), $unitData);

        $response->assertRedirect(route('admin.setting.unit'));
        $response->assertSessionHas('success', 'Unit created');
    }

    public function test_store_unit_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Required
            'symbol' => '', // Required
        ];

        // Mock the UnitService to ensure createUnit is NOT called
        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) {
                $mock->shouldNotReceive('createUnit');
            })
        );

        $response = $this->post(route('admin.setting.unit.store'), $invalidData);

        $response->assertSessionHasErrors(['name', 'symbol']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_it_can_update_a_unit()
    {
        $unit = Unit::factory()->create();
        $updateData = [
            'name' => 'Updated Unit Name',
            'symbol' => 'UUN',
        ];

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $updateData) {
                $mock->shouldReceive('updateUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }), $updateData)->andReturn(['success' => true]);
            })
        );

        $response = $this->put(route('admin.setting.unit.update', $unit->id), $updateData);

        $response->assertRedirect(route('admin.setting.unit'));
        $response->assertSessionHas('success', 'Unit updated');
    }

    public function test_it_can_delete_a_unit()
    {
        $unit = Unit::factory()->create();

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit) {
                $mock->shouldReceive('deleteUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }))->andReturn(['success' => true]);
            })
        );

        $response = $this->delete(route('admin.setting.unit.destroy', $unit->id));

        $response->assertRedirect(route('admin.setting.unit'));
        $response->assertSessionHas('success', 'Unit deleted');
    }

    public function test_store_unit_handles_service_level_error()
    {
        $unitData = [
            'name' => 'Kilogram',
            'symbol' => 'kg',
        ];

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unitData) {
                $mock->shouldReceive('createUnit')->once()->with($unitData)->andReturn(['success' => false, 'message' => 'Service error']);
            })
        );

        $response = $this->post(route('admin.setting.unit.store'), $unitData);

        $response->assertSessionHasErrors(['name' => 'Service error']);
        $response->assertStatus(302);
    }

    public function test_update_unit_handles_service_level_error()
    {
        $unit = Unit::factory()->create();
        $updateData = [
            'name' => 'Updated Unit Name',
            'symbol' => 'UUN',
        ];

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $updateData) {
                $mock->shouldReceive('updateUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }), $updateData)->andReturn(['success' => false, 'message' => 'Update service error']);
            })
        );

        $response = $this->put(route('admin.setting.unit.update', $unit->id), $updateData);

        $response->assertSessionHasErrors(['name' => 'Update service error']);
        $response->assertStatus(302);
    }

    public function test_it_can_store_a_new_unit_via_ajax()
    {
        $unitData = [
            'name' => 'AJAX Unit',
            'symbol' => 'AJX',
        ];

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unitData) {
                $mock->shouldReceive('createUnit')->once()->with($unitData)->andReturn(['success' => true]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.unit.store'), $unitData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Unit created successfully.']);
    }

    public function test_store_unit_handles_service_level_error_via_ajax()
    {
        $unitData = [
            'name' => 'AJAX Unit',
            'symbol' => 'AJX',
        ];
        $errorMessage = 'AJAX creation failed.';

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unitData, $errorMessage) {
                $mock->shouldReceive('createUnit')->once()->with($unitData)->andReturn(['success' => false, 'message' => $errorMessage]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.unit.store'), $unitData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['name' => [$errorMessage]]
            ]);
    }

    public function test_it_can_update_a_unit_via_ajax()
    {
        $unit = Unit::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Unit',
            'symbol' => 'UAJ',
        ];

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $updateData) {
                $mock->shouldReceive('updateUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }), $updateData)->andReturn(['success' => true]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.setting.unit.update', $unit->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Unit updated successfully.']);
    }

    public function test_update_unit_handles_service_level_error_via_ajax()
    {
        $unit = Unit::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Unit',
            'symbol' => 'UAJ',
        ];
        $errorMessage = 'AJAX update failed.';

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $updateData, $errorMessage) {
                $mock->shouldReceive('updateUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }), $updateData)->andReturn(['success' => false, 'message' => $errorMessage]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.setting.unit.update', $unit->id), $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['name' => [$errorMessage]]
            ]);
    }

    public function test_it_can_delete_a_unit_via_ajax()
    {
        $unit = Unit::factory()->create();

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit) {
                $mock->shouldReceive('deleteUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }))->andReturn(['success' => true]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.setting.unit.destroy', $unit->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Unit deleted successfully.']);
    }

    public function test_destroy_unit_handles_service_level_error()
    {
        $unit = Unit::factory()->create();
        $errorMessage = 'Deletion failed.';

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $errorMessage) {
                $mock->shouldReceive('deleteUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }))->andReturn(['success' => false, 'message' => $errorMessage]);
            })
        );

        $response = $this->delete(route('admin.setting.unit.destroy', $unit->id));

        $response->assertRedirect(route('admin.setting.unit'));
        $response->assertSessionHas('error', $errorMessage);
    }

    public function test_destroy_unit_handles_service_level_error_via_ajax()
    {
        $unit = Unit::factory()->create();
        $errorMessage = 'AJAX deletion failed.';

        $this->instance(
            UnitService::class,
            Mockery::mock(UnitService::class, function ($mock) use ($unit, $errorMessage) {
                $mock->shouldReceive('deleteUnit')->once()->with(Mockery::on(function ($arg) use ($unit) {
                    return $arg->id === $unit->id;
                }))->andReturn(['success' => false, 'message' => $errorMessage]);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.setting.unit.destroy', $unit->id));

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }
}