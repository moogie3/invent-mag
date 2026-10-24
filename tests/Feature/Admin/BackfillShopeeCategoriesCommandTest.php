<?php

namespace Tests\Feature\Admin;

use App\Models\Categories;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BackfillShopeeCategoriesCommandTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Categories $legacy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->legacy = Categories::create([
            'name' => 'Shopee Import',
            'description' => 'Auto-created for products imported from Shopee XLSX files',
        ]);
    }

    #[Test]
    public function it_moves_legacy_products_into_the_sku_guide_categories()
    {
        $bySku = Product::factory()->create(['category_id' => $this->legacy->id, 'code' => 'IC-0001']);
        $byName = Product::factory()->create([
            'category_id' => $this->legacy->id,
            'code' => 'XYZ99',
            'name' => 'Saklar Gantung Tactile',
        ]);
        $untouched = Product::factory()->create();

        $this->artisan('categories:backfill-shopee')
            ->assertExitCode(0);

        $bySku->refresh();
        $this->assertSame('IC', $bySku->category->name);
        $this->assertSame('Integrated Circuit (IC)', $bySku->category->description);

        $byName->refresh();
        $this->assertSame('SWT', $byName->category->name);

        $this->assertSame($untouched->category_id, $untouched->fresh()->category_id);
        $this->assertSame(
            0,
            Product::withoutTenancy()->where('category_id', $this->legacy->id)->count()
        );
    }

    #[Test]
    public function it_reports_without_writing_in_dry_run_mode()
    {
        $product = Product::factory()->create(['category_id' => $this->legacy->id, 'code' => 'IC-0001']);

        $this->artisan('categories:backfill-shopee', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame($this->legacy->id, $product->fresh()->category_id);
    }

    #[Test]
    public function it_can_run_for_a_single_tenant_only()
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        $otherLegacy = $otherTenant->execute(fn () => Categories::create([
            'name' => 'Shopee Import',
            'description' => 'Auto-created for products imported from Shopee XLSX files',
        ]));
        $otherProduct = $otherTenant->execute(fn () => Product::factory()->create([
            'category_id' => $otherLegacy->id,
            'code' => 'IC-0001',
        ]));

        $mine = Product::factory()->create(['category_id' => $this->legacy->id, 'code' => 'TRS-0001']);

        $this->artisan('categories:backfill-shopee', ['--tenant' => $this->tenant->id])
            ->assertExitCode(0);

        $this->assertSame('TRS', $mine->fresh()->category->name);
        $this->assertSame($otherLegacy->id, $otherProduct->fresh()->category_id);
        $this->assertSame(0, Categories::withoutTenancy()->where('name', 'IC')->count());

        $this->artisan('categories:backfill-shopee')
            ->assertExitCode(0);

        $otherCategoryId = $otherProduct->fresh()->category_id;
        $this->assertSame(
            'IC',
            Categories::withoutTenancy()->where('id', $otherCategoryId)->value('name')
        );
    }

    #[Test]
    public function it_does_nothing_when_no_legacy_products_exist()
    {
        $this->artisan('categories:backfill-shopee')
            ->assertExitCode(0);

        $this->assertSame(1, Categories::count());
    }
}
