<?php

namespace Tests\Feature\Admin;

use App\Models\Tax;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;

class TaxControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    public function test_update_tax_settings_successfully_via_ajax()
    {
        $updatedData = [
            'name' => 'New Tax AJAX',
            'rate' => 12.3,
            'is_active' => false,
        ];

        $mockService = \Mockery::mock(TaxService::class);
        $mockService->shouldReceive('updateTax')->once()->with($updatedData);
        $this->app->instance(TaxService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.tax.update'), $updatedData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Tax settings updated successfully!']);
    }

    public function test_update_tax_settings_handles_service_exception()
    {
        $updatedData = [
            'name' => 'New Tax',
            'rate' => 15.5,
            'is_active' => true,
        ];
        $errorMessage = 'Service error during tax update.';

        $mockService = \Mockery::mock(TaxService::class);
        $mockService->shouldReceive('updateTax')->once()->with($updatedData)->andThrow(new \Exception($errorMessage));
        $this->app->instance(TaxService::class, $mockService);

        $response = $this->post(route('admin.setting.tax.update'), $updatedData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Error updating tax settings: ' . $errorMessage);
    }

    public function test_update_tax_settings_handles_service_exception_via_ajax()
    {
        $updatedData = [
            'name' => 'New Tax AJAX',
            'rate' => 12.3,
            'is_active' => false,
        ];
        $errorMessage = 'AJAX service error during tax update.';

        $mockService = \Mockery::mock(TaxService::class);
        $mockService->shouldReceive('updateTax')->once()->with($updatedData)->andThrow(new \Exception($errorMessage));
        $this->app->instance(TaxService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.tax.update'), $updatedData);

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => 'Error updating tax settings: ' . $errorMessage]);
    }
}
