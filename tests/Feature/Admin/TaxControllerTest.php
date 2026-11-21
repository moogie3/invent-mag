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

    public function test_index_uses_the_standard_pagination_footer()
    {
        Tax::factory()->count(3)->create();

        $response = $this->get(route('admin.setting.tax'));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            '<div data-pagination-footer class="card-footer',
            $response->getContent(),
            'The footer must carry the pagination marker.'
        );
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
        // Created first, so the lowest-id fallback would pick this one: the
        // endpoint has to answer with the FLAGGED default instead.
        $other = Tax::factory()->create(['rate' => 5, 'type' => 'exclusive', 'is_default' => false]);
        $tax = Tax::factory()->create(['rate' => 11, 'type' => 'exclusive', 'is_default' => true]);

        // No service mock: the controller resolves the default itself now, so
        // the answer has to come from the real resolver.
        $response = $this->get(route('admin.setting.tax.get'));

        $response->assertStatus(200);
        $response->assertJson([
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'tax_name' => $tax->name,
            'tax_id' => $tax->id,
        ]);
        $this->assertNotEquals($other->id, $response->json('tax_id'));
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

    public function test_index_shows_the_tax_the_app_books_with_not_the_flagged_one()
    {
        // setupTenant() leaves tax_jurisdiction NULL, so this tenant is NOT
        // Indonesian: PPN records are excluded from every booking fallback by
        // the jurisdiction scope, even when one of them carries is_default.
        $jurisdictionTax = Tax::factory()->create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'is_ppn' => true,
            'is_pph' => false,
            'is_default' => true,
            'is_active' => true,
        ]);
        $bookedWith = Tax::factory()->create([
            'name' => 'Non-PKP 0%',
            'rate' => 0,
            'is_ppn' => false,
            'is_pph' => false,
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->get(route('admin.setting.tax'));

        $response->assertStatus(200);

        $defaultTax = $response->viewData('defaultTax');
        $this->assertNotNull($defaultTax, 'The page must always show which tax is in effect.');
        $this->assertEquals($bookedWith->id, $defaultTax->id);
        $this->assertNotEquals(
            $jurisdictionTax->id,
            $defaultTax->id,
            'A flagged tax this jurisdiction cannot use must not be shown as the default.'
        );
    }

    public function test_store_tax_persists_the_ppn_and_pph_flags()
    {
        // Real TaxService: the point of this test is that the flags survive
        // the whole request, not that the controller forwards an array.
        $response = $this->post(route('admin.setting.tax.store'), [
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'description' => 'Output VAT',
            'is_active' => 1,
            'is_default' => 0,
            'is_ppn' => 1,
            'is_pph' => 0,
        ]);

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');

        $tax = Tax::where('name', 'PPN 11%')->first();
        $this->assertNotNull($tax);
        $this->assertTrue((bool) $tax->is_ppn);
        $this->assertFalse((bool) $tax->is_pph);
    }

    public function test_update_tax_turns_the_ppn_flag_off_when_the_switch_is_unchecked()
    {
        $tax = Tax::factory()->create(['is_ppn' => true, 'is_pph' => true]);

        // The create/edit modal puts a hidden 0 before each switch, so an
        // unchecked box submits 0 rather than omitting the key. An omitted key
        // means "keep the old value", which is exactly what must NOT happen
        // when someone switches PPN off.
        $response = $this->put(route('admin.setting.tax.update', $tax->id), [
            'name' => $tax->name,
            'rate' => $tax->rate,
            'type' => $tax->type,
            'is_active' => 1,
            'is_default' => 0,
            'is_ppn' => 0,
            'is_pph' => 1,
        ]);

        $response->assertRedirect(route('admin.setting.tax'));
        $response->assertSessionHas('success');

        $fresh = $tax->fresh();
        $this->assertFalse((bool) $fresh->is_ppn, 'Posting is_ppn=0 must clear the flag.');
        $this->assertTrue((bool) $fresh->is_pph, 'The untouched switch must keep its value.');
    }
}
