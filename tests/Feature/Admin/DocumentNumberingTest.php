<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Sales;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * Document numbers are per-tenant sequences: every tenant starts its own
 * INV-0001 and its own QTN-<today>-0001. The unique keys behind them were
 * global, so the second tenant to reach that number failed on an integrity
 * violation instead of filing its own document.
 */
class DocumentNumberingTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        Warehouse::factory()->create(['is_main' => true]);
    }

    private function becomeSecondTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Second Tenant', 'domain' => 'second.localhost']);
        $tenant->makeCurrent();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('superuser');
        $this->actingAs($user);

        return $tenant;
    }

    public function test_a_second_tenant_can_issue_the_same_invoice_number()
    {
        Sales::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invoice' => 'INV-0001',
        ]);

        $second = $this->becomeSecondTenant();

        // Each tenant runs its own sequence, so the second tenant's first
        // invoice is INV-0001 again...
        $this->assertSame('INV-0001', app(SalesService::class)->generateInvoiceNumber());

        // ...and both rows have to sit in the same table side by side.
        Sales::factory()->create([
            'tenant_id' => $second->id,
            'invoice' => 'INV-0001',
        ]);

        $this->assertDatabaseHas('sales', ['tenant_id' => $this->tenant->id, 'invoice' => 'INV-0001']);
        $this->assertDatabaseHas('sales', ['tenant_id' => $second->id, 'invoice' => 'INV-0001']);
    }

    public function test_a_second_tenant_can_issue_the_same_quotation_number()
    {
        $payload = [
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Line', 'quantity' => 1, 'price' => 100, 'discount' => 0, 'total' => 100],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
        ];

        $customerA = Customer::factory()->create();
        $this->post(route('admin.quotations.store'), $payload + ['customer_id' => $customerA->id])
            ->assertSessionHasNoErrors();
        $numberA = Quotation::firstOrFail()->quotation_number;

        $second = $this->becomeSecondTenant();

        $customerB = Customer::factory()->create();
        $response = $this->post(route('admin.quotations.store'), $payload + ['customer_id' => $customerB->id]);

        $response->assertSessionHasNoErrors();

        $quotationB = Quotation::firstOrFail();
        $this->assertSame($numberA, $quotationB->quotation_number);
        $this->assertDatabaseHas('quotations', [
            'tenant_id' => $second->id,
            'quotation_number' => $numberA,
        ]);
    }

    public function test_the_invoice_generator_skips_numbers_already_on_file()
    {
        Sales::factory()->create(['tenant_id' => $this->tenant->id, 'invoice' => 'INV-0042']);
        Sales::factory()->create(['tenant_id' => $this->tenant->id, 'invoice' => 'INV-0041']);
        Sales::factory()->create(['tenant_id' => $this->tenant->id, 'invoice' => 'POS-0001']);

        // The newest row is a POS sale and INV-0042 is already taken, so
        // the next invoice is INV-0043: not a restart at INV-0001 (the old
        // code read the newest row of any prefix and matched no INV digits)
        // and not INV-0042 (already on file).
        $this->assertSame('INV-0043', app(SalesService::class)->generateInvoiceNumber());
    }
}
