<?php

namespace Tests\Feature\Admin;

use App\Models\Tax;
use App\Services\TaxService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TaxControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_displays_tax_settings_page()
    {
        Tax::factory()->count(3)->create();

        $response = $this->get(route('admin.setting.tax'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tax.tax');
        $response->assertViewHas('taxes');
        $response->assertViewHas('totalTaxes');
    }

    public function test_store_tax_successfully()
    {
        $taxData = [
            'name' => 'PPN',
            'rate' => 11,
            'type' => 'exclusive',
            'description' => 'Pajak Pertambahan Nilai',
            'is_active' => true,
        ];

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($taxData) {
                $mock->shouldReceive('create')->once()->with($taxData);
            })
        );

        $response = $this->post(route('admin.setting.tax.store'), $taxData);

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');
    }

    public function test_store_tax_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '',
            'rate' => 'abc',
            'type' => 'invalid',
        ];

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) {
                $mock->shouldNotReceive('create');
            })
        );

        $response = $this->post(route('admin.setting.tax.store'), $invalidData);

        $response->assertSessionHasErrors(['name', 'rate', 'type']);
        $response->assertStatus(302);
    }

    public function test_it_can_update_a_tax()
    {
        $tax = Tax::factory()->create();
        $updateData = [
            'name' => 'Updated Tax',
            'rate' => 15,
            'type' => 'inclusive',
            'description' => 'Updated description',
            'is_active' => true,
        ];

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax, $updateData) {
                $mock->shouldReceive('update')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    }),
                    $updateData
                );
            })
        );

        $response = $this->put(route('admin.setting.tax.update', $tax->id), $updateData);

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');
    }

    public function test_it_can_delete_a_tax()
    {
        $tax = Tax::factory()->create();

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('delete')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    })
                );
            })
        );

        $response = $this->delete(route('admin.setting.tax.destroy', $tax->id));

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');
    }

    public function test_it_can_set_default_tax()
    {
        $tax = Tax::factory()->create();

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('setDefault')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    })
                );
            })
        );

        $response = $this->post(route('admin.setting.tax.default', $tax->id));

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');
    }

    public function test_it_can_set_default_tax_via_ajax()
    {
        $tax = Tax::factory()->create();

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('setDefault')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    })
                );
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.tax.default', $tax->id));

        $response->assertJson(['success' => true]);
    }

    public function test_get_tax_rate_returns_default_tax()
    {
        $tax = Tax::factory()->create(['rate' => 11, 'type' => 'exclusive']);

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('getDefault')->once()->andReturn($tax);
            })
        );

        $response = $this->get(route('admin.setting.tax.get'));

        $response->assertStatus(200);
        $response->assertJson([
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'tax_name' => $tax->name,
            'tax_id' => $tax->id,
        ]);
    }

    public function test_get_tax_rate_with_specific_id()
    {
        $tax = Tax::factory()->create(['rate' => 12, 'type' => 'inclusive']);

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('findById')->once()->with($tax->id)->andReturn($tax);
            })
        );

        $response = $this->get(route('admin.setting.tax.get', ['tax_id' => $tax->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'tax_rate' => 12,
            'tax_type' => 'inclusive',
            'tax_name' => $tax->name,
            'tax_id' => $tax->id,
        ]);
    }

    public function test_store_tax_handles_service_exception()
    {
        $taxData = [
            'name' => 'PPN',
            'rate' => 11,
            'type' => 'exclusive',
        ];
        $errorMessage = 'Service error during tax creation.';

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($taxData, $errorMessage) {
                $mock->shouldReceive('create')->once()->with($taxData)->andThrow(new \Exception($errorMessage));
            })
        );

        $response = $this->post(route('admin.setting.tax.store'), $taxData);

        $response->assertRedirect();
        $response->assertSessionHas('error', $errorMessage);
    }

    public function test_store_tax_successfully_via_ajax()
    {
        $taxData = [
            'name' => 'PPN AJAX',
            'rate' => 11,
            'type' => 'exclusive',
        ];

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($taxData) {
                $mock->shouldReceive('create')->once()->with($taxData);
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.tax.store'), $taxData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_store_tax_handles_service_exception_via_ajax()
    {
        $taxData = [
            'name' => 'PPN AJAX',
            'rate' => 11,
            'type' => 'exclusive',
        ];
        $errorMessage = 'AJAX service error during tax creation.';

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($taxData, $errorMessage) {
                $mock->shouldReceive('create')->once()->with($taxData)->andThrow(new \Exception($errorMessage));
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.tax.store'), $taxData);

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }

    public function test_update_tax_successfully_via_ajax()
    {
        $tax = Tax::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Tax',
            'rate' => 15,
            'type' => 'inclusive',
        ];

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax, $updateData) {
                $mock->shouldReceive('update')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    }),
                    $updateData
                );
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.setting.tax.update', $tax->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_update_tax_handles_service_exception_via_ajax()
    {
        $tax = Tax::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Tax',
            'rate' => 15,
            'type' => 'inclusive',
        ];
        $errorMessage = 'AJAX update failed.';

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax, $updateData, $errorMessage) {
                $mock->shouldReceive('update')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    }),
                    $updateData
                )->andThrow(new \Exception($errorMessage));
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.setting.tax.update', $tax->id), $updateData);

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }

    public function test_delete_tax_successfully_via_ajax()
    {
        $tax = Tax::factory()->create();

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax) {
                $mock->shouldReceive('delete')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    })
                );
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.setting.tax.destroy', $tax->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_delete_tax_handles_service_exception_via_ajax()
    {
        $tax = Tax::factory()->create();
        $errorMessage = 'AJAX deletion failed.';

        $this->instance(
            TaxService::class,
            Mockery::mock(TaxService::class, function ($mock) use ($tax, $errorMessage) {
                $mock->shouldReceive('delete')->once()->with(
                    Mockery::on(function ($arg) use ($tax) {
                        return $arg->id === $tax->id;
                    })
                )->andThrow(new \Exception($errorMessage));
            })
        );

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.setting.tax.destroy', $tax->id));

        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => $errorMessage]);
    }
}
