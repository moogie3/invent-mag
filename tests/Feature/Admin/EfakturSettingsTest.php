<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class EfakturSettingsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->actingAs($this->user);
    }

    public function test_settings_page_renders_when_pkp_date_is_set(): void
    {
        $this->tenant->update([
            'tax_jurisdiction' => 'ID',
            'npwp' => '01.234.567.8-901.000',
            'pkp_status' => true,
            'pkp_date' => '2024-05-01',
            'ppn_rate' => 11.00,
        ]);

        $response = $this->get(route('admin.efaktur.settings'));

        $response->assertOk();
        $response->assertSee('2024-05-01');
    }

    public function test_settings_page_renders_when_pkp_date_is_null(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $response = $this->get(route('admin.efaktur.settings'));

        $response->assertOk();
    }

    public function test_saving_settings_with_pkp_date_keeps_page_rendering(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $response = $this->post(route('admin.efaktur.settings.save'), [
            'tax_jurisdiction' => 'ID',
            'npwp' => '01.234.567.8-901.000',
            'pkp_status' => '1',
            'pkp_date' => '2024-05-01',
            'ppn_rate' => 11.00,
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->get(route('admin.efaktur.settings'))->assertOk();
    }

    public function test_tenant_pkp_date_is_cast_to_carbon(): void
    {
        $this->tenant->update(['pkp_date' => '2024-05-01']);

        $this->assertInstanceOf(Carbon::class, $this->tenant->fresh()->pkp_date);
        $this->assertSame('2024-05-01', $this->tenant->fresh()->pkp_date->format('Y-m-d'));
    }

    public function test_customer_pkp_date_is_cast_to_carbon(): void
    {
        $customer = Customer::factory()->create([
            'pkp_date' => '2024-05-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $customer->fresh()->pkp_date);
        $this->assertSame('2024-05-01', $customer->fresh()->pkp_date->format('Y-m-d'));
    }
}
