<?php

namespace Tests\Unit\Services;

use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TaxServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services
        $this->taxService = new TaxService;
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(TaxService::class, $this->taxService);
    }

    #[Test]
    public function it_can_update_tax_settings()
    {
        $tax = Tax::factory()->create();
        $data = [
            'name' => 'VAT',
            'rate' => 20,
            'type' => 'exclusive',
            'description' => null,
            'is_active' => true,
            'is_default' => false,
        ];

        $updated = $this->taxService->update($tax, $data);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => 'VAT',
            'rate' => 20,
            'is_active' => true,
        ]);
        $this->assertInstanceOf(Tax::class, $updated);
    }

    #[Test]
    public function it_can_create_tax_settings_if_none_exist()
    {
        $data = [
            'name' => 'GST',
            'rate' => 5,
            'type' => 'exclusive',
            'description' => null,
            'is_active' => true,
            'is_default' => false,
        ];

        $tax = $this->taxService->create($data);

        $this->assertDatabaseHas('taxes', [
            'name' => 'GST',
            'rate' => 5,
            'is_active' => true,
        ]);
        $this->assertInstanceOf(Tax::class, $tax);
    }

    #[Test]
    public function it_resolves_one_ordered_default_for_every_document()
    {
        // No flagged default at all: the answer has to be the OLDEST active
        // tax, not whichever row the database happened to hand back first.
        Tax::factory()->create(['name' => 'First', 'is_active' => true, 'is_default' => false]);
        Tax::factory()->create(['name' => 'Second', 'is_active' => true, 'is_default' => false]);
        $third = Tax::factory()->create(['name' => 'Third', 'is_active' => true, 'is_default' => false]);

        $resolved = Tax::effectiveDefault();

        $this->assertNotNull($resolved);
        $this->assertSame('First', $resolved->name, 'The fallback must be deterministic (lowest id).');

        // A flagged default still wins over the age order...
        $flagged = Tax::factory()->create(['name' => 'Flagged', 'is_active' => true, 'is_default' => true]);
        $this->assertSame($flagged->id, Tax::effectiveDefault()->id);

        // ...and an inactive flagged row is not reachable through the
        // jurisdiction scope, so the resolver keeps walking.
        $flagged->update(['is_active' => false]);
        $this->assertSame('First', Tax::effectiveDefault()->name);
        $this->assertNotSame($third->id, Tax::effectiveDefault()->id);
    }

    #[Test]
    public function deleting_the_default_promotes_a_tax_that_can_actually_be_one()
    {
        $current = Tax::factory()->create([
            'name' => 'Zero',
            'rate' => 0,
            'type' => Tax::TYPE_NONE,
            'is_active' => true,
            'is_default' => true,
            'is_pph' => false,
        ]);
        // Created between the default and the candidate, so an unordered
        // "first active" query would pick this withholding tax as the next
        // default and every new document would be created with a 2% deduction.
        Tax::factory()->create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => Tax::TYPE_EXCLUSIVE,
            'is_active' => true,
            'is_default' => false,
            'is_pph' => true,
        ]);
        $candidate = Tax::factory()->create([
            'name' => 'VAT 11%',
            'rate' => 11,
            'type' => Tax::TYPE_EXCLUSIVE,
            'is_active' => true,
            'is_default' => false,
            'is_pph' => false,
        ]);

        $this->taxService->delete($current);

        $freshDefault = Tax::effectiveDefault();
        $this->assertNotNull($freshDefault);
        $this->assertSame($candidate->id, $freshDefault->id,
            'Deleting the default must promote the next non-withholding tax, in a fixed order.');
        $this->assertFalse((bool) $freshDefault->is_pph);
        $this->assertTrue((bool) $freshDefault->fresh()->is_default);
    }
}
