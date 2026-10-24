<?php

namespace App\Console\Commands;

use App\Models\Categories;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Shopee\ShopeeCategoryResolver;
use Illuminate\Console\Command;

/**
 * One-off repair for products imported before the category resolver existed:
 * they all point at the shared "Shopee Import" bucket. Re-runs the same
 * resolution the importer now performs (SKU prefix → name keywords → MISC)
 * per tenant and reports where products landed. Safe to run repeatedly —
 * already-categorized products are skipped.
 */
class BackfillShopeeCategories extends Command
{
    protected $signature = 'categories:backfill-shopee
        {--tenant= : Only run for this tenant ID}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Move products out of the legacy "Shopee Import" category into the proper SKU guide categories';

    public function handle(): int
    {
        $tenants = Tenant::query();
        if ($tenantId = $this->option('tenant')) {
            $tenants->where('id', $tenantId);
        }

        $dryRun = (bool) $this->option('dry-run');
        $grandTotal = 0;

        foreach ($tenants->get() as $tenant) {
            $moved = $tenant->execute(fn () => $this->backfillTenant($tenant, $dryRun));
            if ($moved > 0) {
                $this->info(sprintf(
                    'Tenant %d (%s): %d product%s %s.',
                    $tenant->id,
                    $tenant->name,
                    $moved,
                    $moved === 1 ? '' : 's',
                    $dryRun ? 'would move' : 'moved'
                ));
            }
            $grandTotal += $moved;
        }

        if ($grandTotal === 0) {
            $this->info('Nothing to do — no products found in "Shopee Import" categories.');
        }

        return self::SUCCESS;
    }

    private function backfillTenant(Tenant $tenant, bool $dryRun): int
    {
        $legacyIds = Categories::query()
            ->whereRaw('LOWER(name) = ?', ['shopee import'])
            ->pluck('id');

        if ($legacyIds->isEmpty()) {
            return 0;
        }

        $resolver = app(ShopeeCategoryResolver::class);
        $moved = 0;
        $perCategory = [];

        // Load IDs up front: chunking while the update removes rows from the
        // WHERE category_id filter would silently skip products (offset shift).
        $productIds = Product::query()
            ->whereIn('category_id', $legacyIds)
            ->orderBy('id')
            ->pluck('id');

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if (! $product) {
                continue;
            }

            $category = $resolver->resolve((string) $product->code, (string) $product->name);

            if (! $dryRun && $category->id !== $product->category_id) {
                $product->update(['category_id' => $category->id]);
            }

            $moved++;
            $key = $category->name;
            $perCategory[$key] = ($perCategory[$key] ?? 0) + 1;
        }

        if ($moved > 0) {
            ksort($perCategory);
            $this->table(
                ['Category', 'Products'],
                collect($perCategory)->map(fn ($count, $name) => [$name, $count])->all()
            );
        }

        return $moved;
    }
}
