<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class TaxIsPphTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function it_can_create_tax_with_pph_flag()
    {
        $tax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $this->assertTrue($tax->is_pph);
    }

    #[Test]
    public function it_defaults_is_pph_to_false()
    {
        $tax = Tax::create([
            'name' => 'Regular Tax',
            'rate' => 10,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => false,
        ]);

        $this->assertFalse($tax->is_pph);
    }

    #[Test]
    public function it_can_filter_pph_taxes()
    {
        Tax::create(['name' => 'PPN 11%', 'rate' => 11, 'type' => 'exclusive', 'is_pph' => false]);
        Tax::create(['name' => 'PPh 23', 'rate' => 2, 'type' => 'exclusive', 'is_pph' => true]);

        $pphTaxes = Tax::where('is_pph', true)->get();
        $this->assertCount(1, $pphTaxes);
        $this->assertEquals('PPh 23', $pphTaxes->first()->name);
    }

    #[Test]
    public function it_pph_flag_does_not_affect_is_ppn()
    {
        $pphTax = Tax::create(['name' => 'PPh 23', 'rate' => 2, 'type' => 'exclusive', 'is_pph' => true, 'is_ppn' => false]);
        $ppnTax = Tax::create(['name' => 'PPN 11%', 'rate' => 11, 'type' => 'exclusive', 'is_pph' => false, 'is_ppn' => true]);

        $this->assertTrue($pphTax->is_pph);
        $this->assertFalse($pphTax->is_ppn);
        $this->assertFalse($ppnTax->is_pph);
        $this->assertTrue($ppnTax->is_ppn);
    }

    #[Test]
    public function it_pph_tax_rate_is_float_casted()
    {
        $tax = Tax::create([
            'name' => 'PPh 23',
            'rate' => '2.5',
            'type' => 'exclusive',
            'is_pph' => true,
        ]);

        $this->assertIsFloat($tax->rate);
        $this->assertEquals(2.5, $tax->rate);
    }

    #[Test]
    public function it_can_update_pph_flag()
    {
        $tax = Tax::create([
            'name' => 'Custom Tax',
            'rate' => 5,
            'type' => 'exclusive',
            'is_pph' => false,
        ]);

        $this->assertFalse($tax->is_pph);

        $tax->update(['is_pph' => true]);
        $this->assertTrue($tax->fresh()->is_pph);
    }
}
