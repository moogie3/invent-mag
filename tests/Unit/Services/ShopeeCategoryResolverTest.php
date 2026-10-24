<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\Tenant;
use App\Services\Shopee\ShopeeCategoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ShopeeCategoryResolverTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private ShopeeCategoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->resolver = app(ShopeeCategoryResolver::class);
    }

    #[Test]
    public function it_resolves_by_sku_prefix_including_aliases()
    {
        $trs = $this->resolver->resolve('TRS-0001', 'Nama Apapun');
        $this->assertSame('TRS', $trs->name);
        $this->assertSame('Transistor', $trs->description);

        $fly = $this->resolver->resolve('FBT-0007', 'Nama Apapun');
        $this->assertSame('FLY', $fly->name);
        $this->assertSame('Flyback', $fly->description);

        $trsViaLegacyPrefix = $this->resolver->resolve('TR-0002', 'Nama Apapun');
        $this->assertSame('TRS', $trsViaLegacyPrefix->name);

        $this->assertSame(2, Categories::count());
    }

    #[Test]
    public function it_ignores_codes_without_a_valid_prefix_and_uses_name_keywords()
    {
        $category = $this->resolver->resolve('57907016200', 'Kapasitor Elco 470uf 25v');

        $this->assertSame('CAP', $category->name);
        $this->assertSame('Capacitor', $category->description);
    }

    #[Test]
    public function it_resolves_by_name_keywords_most_specific_category_first()
    {
        $cases = [
            ['Transistor IGBT Mosfet 20A', 'IGBT'],
            ['MOSFET IRF540N Switching', 'MOS'],
            ['Dioda Schottky 1N5819', 'DIO'],
            ['Flyback Samsung CRT', 'FLY'],
            ['Transistor Darlington TIP122', 'TRS'],
            ['Termistor NTC 10K', 'NTC'],
            ['Sekring Thermal Fuse 10A', 'FUS'],
            ['Resistor Kapur 10W 100 Ohm', 'RES'],
            ['Dinamo Mesin Cuci Sharp', 'DWM'],
            ['Dinamo Kipas Angin Miyako', 'DFN'],
            ['Seal Pembuangan Mesin Cuci LG', 'WMP'],
            ['Lampu LED 9W Putih', 'LED'],
            ['Bohlam Indikator Merah', 'LMP'],
            ['Saklar Gantung Tactile', 'SWT'],
            ['Baterai AA Alkaline', 'BAT'],
            ['Remot TV Universal', 'RMT'],
            ['Kabel Listrik 5M Hitam', 'CBL'],
            ['Jack DC 5V Connector', 'CON'],
            ['Stop Kontak 3 Pin', 'SOC'],
            ['Obeng Set 6 in 1', 'TL'],
            ['Multitester Analog DT830', 'MTR'],
            ['Heating Element Rice Cooker', 'HTE'],
            ['Gigi Gear Blender Philips', 'GER'],
            ['Adaptor 12V 2A Power Supply', 'ADP'],
            ['Kit Power Amplifier OCL', 'PAK'],
            ['Overload Protector Pompa Air', 'OVL'],
            ['Relay Defrost Kulkas', 'RLY'],
            ['Thermostat Bimetal Rice Cooker', 'THM'],
            ['MCB 8 Ampere MCB', 'MCB'],
            ['Lem Besi Super Power Glue', 'GLU'],
        ];

        foreach ($cases as [$name, $expectedPrefix]) {
            $this->assertSame(
                $expectedPrefix,
                $this->resolver->resolve('', $name)->name,
                "Name \"{$name}\" should resolve to {$expectedPrefix}"
            );
        }
    }

    #[Test]
    public function it_reuses_an_existing_category_by_prefix_without_duplicating_it()
    {
        $existing = Categories::create(['name' => 'fly', 'description' => 'Flyback Lama']);

        $resolved = $this->resolver->resolve('FLY-0010', '');

        $this->assertTrue($resolved->is($existing));
        $this->assertSame(1, Categories::count());
    }

    #[Test]
    public function it_reuses_an_existing_category_matching_the_description_label()
    {
        $existing = Categories::create(['name' => 'Kapasitor Lama', 'description' => 'Capacitor']);

        $resolved = $this->resolver->resolve('', 'Kapasitor Elco 2200uf');

        $this->assertTrue($resolved->is($existing));
        $this->assertSame(1, Categories::count());
    }

    #[Test]
    public function it_matches_the_shopee_leaf_against_existing_category_names()
    {
        $existing = Categories::create(['name' => 'Pliers', 'description' => 'Pliers bucket']);

        $resolved = $this->resolver->resolve('', 'Barang Tanpa Pola Asing', '101798 - Home & Living/Tools/Pliers');

        $this->assertTrue($resolved->is($existing));
    }

    #[Test]
    public function it_falls_back_to_the_guide_catch_all_category()
    {
        $fallback = $this->resolver->resolve('', 'Aneka Barang Unik Rumah Tangga');

        $this->assertSame('MISC', $fallback->name);
        $this->assertSame('Other Spare Part', $fallback->description);
    }

    #[Test]
    public function it_creates_categories_per_tenant_rather_than_globally()
    {
        $first = $this->resolver->resolve('TRS-0001', '');

        $otherTenant = Tenant::create(['name' => 'Second Tenant', 'domain' => 'second.localhost']);
        $otherCategory = $otherTenant->execute(fn () => $this->resolver->resolve('TRS-0001', ''));

        $this->assertNotSame($first->id, $otherCategory->id);
        $this->assertSame($first->name, $otherCategory->name);
        $this->assertSame(2, Categories::withoutTenancy()->count());
    }
}
