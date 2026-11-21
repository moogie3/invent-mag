<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Tax;
use App\Models\Tenant;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * The tax fields on sales, purchases, quotations and POS all used to be
 * validated with a bare `exists:taxes,id`: a raw table lookup that knows
 * nothing about the tenant, the jurisdiction or whether the tax is switched
 * on. The writer resolved such an id to null and booked 0% without saying a
 * word, so a bad id was indistinguishable from a deliberate zero tax.
 */
class TaxSelectionValidationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Customer $customer;

    protected Tax $tax;

    protected Tenant $otherTenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        Warehouse::factory()->create(['is_main' => true]);
        $this->customer = Customer::factory()->create();
        $this->tax = Tax::factory()->create([
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
        ]);

        // A second company, so "belongs to someone else" is a real row behind
        // a real foreign key rather than a hand-written tenant id.
        $this->otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        $this->tenant->makeCurrent();
    }

    /**
     * @return array<string, mixed>
     */
    private function quotationPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ], $overrides);
    }

    public function test_a_tax_from_another_company_is_rejected()
    {
        // Same table, different tenant: the bare exists rule said yes to this.
        $foreignTax = Tax::forceCreate([
            'name' => 'Foreign VAT',
            'rate' => 20,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => false,
            'tenant_id' => $this->otherTenant->id,
        ]);

        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['tax_id' => $foreignTax->id])
        );

        $response->assertSessionHasErrors(['tax_id']);
        $this->assertSame(0, Quotation::count(), 'The document must not be written at all.');
    }

    public function test_a_tax_that_does_not_exist_is_rejected()
    {
        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['tax_id' => 999999])
        );

        $response->assertSessionHasErrors(['tax_id']);
        $this->assertSame(0, Quotation::count());
    }

    public function test_a_switched_off_tax_is_rejected()
    {
        $inactive = Tax::factory()->create(['is_active' => false, 'is_default' => false]);

        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['tax_id' => $inactive->id])
        );

        $response->assertSessionHasErrors(['tax_id']);
        $this->assertSame(0, Quotation::count());
    }

    public function test_a_ppn_tax_is_rejected_for_a_non_indonesian_tenant()
    {
        // setupTenant() leaves tax_jurisdiction NULL, so this tenant is not
        // Indonesian and must not be able to pick a PPN record by hand: the
        // dropdown filters it out, and nothing in the service would have
        // stopped the 11% from being charged with no VAT liability behind it.
        $this->assertFalse(app('currentTenant')->isIndonesian());
        $ppnTax = Tax::factory()->create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => false,
            'is_ppn' => true,
        ]);

        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['tax_id' => $ppnTax->id])
        );

        $response->assertSessionHasErrors(['tax_id']);
        $this->assertSame(0, Quotation::count());
    }

    public function test_an_indonesian_tenant_may_still_pick_that_ppn_tax()
    {
        // Control for the test above: the same tax, the same request, on a
        // tenant whose jurisdiction allows it, has to go through.
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        $ppnTax = Tax::factory()->create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => false,
            'is_ppn' => true,
        ]);

        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['tax_id' => $ppnTax->id])
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Quotation::count());
        $this->assertSame($ppnTax->id, Quotation::first()->tax_id);
    }

    public function test_a_pph_tax_from_another_company_is_rejected_too()
    {
        // The PPh field deliberately skips the jurisdiction check (the
        // service's isIndonesian() gate owns that), but tenancy and activity
        // are checked on both fields.
        $foreignPph = Tax::forceCreate([
            'name' => 'Foreign PPh',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => false,
            'is_pph' => true,
            'tenant_id' => $this->otherTenant->id,
        ]);

        $response = $this->post(
            route('admin.quotations.store'),
            $this->quotationPayload(['pph_tax_id' => $foreignPph->id])
        );

        $response->assertSessionHasErrors(['pph_tax_id']);
        $this->assertSame(0, Quotation::count());
    }
}
