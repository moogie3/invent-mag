<?php

namespace Tests\Feature\Admin;

use App\Models\Tax;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

    public function test_index_displays_tax_settings_page()
    {
        $taxData = ['name' => 'VAT', 'rate' => 10, 'is_active' => true];

        // Mock the TaxService
        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($taxData) {
                $mock->shouldReceive('getTaxData')->once()->andReturn($taxData);
            })
        );

        $response = $this->get(route('admin.setting.tax'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tax.tax');
        $response->assertViewHas('tax', $taxData);
    }

    public function test_update_tax_settings_successfully()
    {
        $initialTax = Tax::factory()->create(['name' => 'Old Tax', 'rate' => 5, 'is_active' => false]);
        $updatedData = [
            'name' => 'New Tax',
            'rate' => 15.5,
            'is_active' => true,
        ];

        // Mock the TaxService
        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($updatedData) {
                $mock->shouldReceive('updateTax')->once()->with($updatedData);
            })
        );

        $response = $this->post(route('admin.setting.tax.update'), $updatedData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Tax settings updated successfully!');
    }

    public function test_update_tax_settings_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Invalid: required
            'rate' => 'abc', // Invalid: numeric
            'is_active' => 'not-a-boolean', // Invalid: boolean
        ];

        // Mock the TaxService to ensure updateTax is NOT called
        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) {
                $mock->shouldNotReceive('updateTax');
            })
        );

        $response = $this->post(route('admin.setting.tax.update'), $invalidData);

        $response->assertSessionHasErrors(['name', 'rate', 'is_active']);
        $response->assertStatus(302); // Redirect back on validation error
    }
}
