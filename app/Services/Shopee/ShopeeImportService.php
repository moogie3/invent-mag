<?php

namespace App\Services\Shopee;

use App\Jobs\DownloadShopeeCoverImages;
use App\Models\ActivityLog;
use App\Models\Categories;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ActivityLogService;
use App\Services\PlanService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Imports Shopee Seller Centre "mass update" XLSX exports (basic/media/sales/shipping)
 * into the local product catalog.
 *
 * Matching key: products.code = Shopee "SKU Induk" (parent_sku) for the family
 * anchor; derived codes (child SKU or "{parent}-{slug(variation name)}") for
 * sibling variants.
 *
 * Variation handling: products with multiple Shopee variations are SPLIT into
 * one sellable product per variation (exact price + stock each). Variant #1
 * keeps the parent SKU as its code and acts as the family anchor; variants
 * #2..N point back via products.parent_id. Single-variation products import
 * exactly like a plain product. Weight/dimensions/shipping channels are not
 * stored.
 */
class ShopeeImportService
{
    public function __construct(
        private readonly ShopeeXlsxParser $parser,
        private readonly PlanService $planService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * Cache key for the modal progress bar. Scoped per user + per-submission
     * client token so concurrent imports never cross-read.
     */
    public static function progressCacheKey(?int $userId, string $token): string
    {
        return sprintf('shopee_import_progress.%d.%s', $userId ?? 0, $token);
    }

    /**
     * @param  array<string, string>  $paths  file paths keyed by basic/media/sales/shipping
     * @param  string|null  $progressToken  client token for the polled progress bar (null = no UI polling)
     * @return array<string, mixed> summary counts for the UI
     */
    public function import(array $paths, ?string $progressToken = null): array
    {
        $report = function (string $phase, int $processed = 0, int $total = 0) use ($progressToken): void {
            if ($progressToken === null || $progressToken === '') {
                return;
            }

            Cache::put(
                self::progressCacheKey(auth()->id(), $progressToken),
                ['phase' => $phase, 'processed' => $processed, 'total' => $total, 'updated_at' => time()],
                now()->addMinutes(15)
            );
        };

        $report('parsing');
        $basic = $this->parser->parse($paths['basic'], 'basic_info');
        $media = isset($paths['media']) ? $this->parser->parse($paths['media'], 'media_info') : [];
        $sales = isset($paths['sales']) ? $this->parser->parse($paths['sales'], 'sales_info') : [];
        $shipping = isset($paths['shipping']) ? $this->parser->parse($paths['shipping'], 'shipping_info') : [];

        $products = $this->merge($basic, $media, $sales);
        $totalFamilies = count($products);
        $report('products', 0, $totalFamilies);

        $summary = [
            'total_products' => $totalFamilies,
            'updated' => 0,
            'created' => 0,
            'skipped_plan_limit' => 0,
            'split_families' => 0,
            'stock_skipped' => 0,
            'images_queued' => 0,
            'shipping_rows_ignored' => count($shipping),
            'errors' => [],
        ];

        $productLimit = $this->planService->getProductLimit();
        $currentCount = Product::count();
        $covers = [];
        $processed = 0;

        foreach ($products as $family) {
            $processed++;
            if ($processed % 5 === 0 || $processed === $totalFamilies) {
                $report('products', $processed, $totalFamilies);
            }

            $parentCode = trim((string) ($family['code'] ?? ''));

            if ($parentCode === '') {
                $summary['errors'][] = ($family['product_id'] ?? '?').': missing SKU Induk (parent SKU).';

                continue;
            }

            $targets = $this->targetsFor($family, $parentCode);
            $split = count($family['variants']) > 1;

            if ($split) {
                $summary['split_families']++;
            }

            $anchor = null;

            foreach ($targets as $idx => $target) {
                $code = $target['code'];

                try {
                    $product = Product::where('code', $code)->first();

                    if ($product === null) {
                        if ($productLimit >= 0 && $currentCount >= $productLimit) {
                            $summary['skipped_plan_limit']++;

                            continue;
                        }

                        $product = $this->createProduct($target, $family, $idx === 0 ? null : $anchor?->id);
                        $currentCount++;
                        $summary['created']++;
                    } else {
                        $this->updateProduct($product, $target, $family, $summary);
                        $summary['updated']++;
                    }

                    $this->syncStock($product, $target, $summary);

                    $cover = trim((string) ($family['cover_url'] ?? ''));
                    if ($cover !== '') {
                        $covers[] = [
                            'product_id' => $product->id,
                            'url' => $cover,
                            'current_image' => $product->getRawOriginal('image'),
                        ];
                    }

                    if ($idx === 0) {
                        $anchor = $product;
                    }
                } catch (InvalidArgumentException $e) {
                    $summary['errors'][] = "{$code}: {$e->getMessage()}";
                } catch (\Throwable $e) {
                    Log::warning('Shopee import row failed', ['code' => $code, 'error' => $e->getMessage()]);
                    $summary['errors'][] = "{$code}: import failed ({$e->getMessage()})";
                }
            }
        }

        $report('queuing_images');
        $summary['images_queued'] = $this->queueCoverDownloads($covers, $summary['errors']);

        if ($summary['updated'] > 0 || $summary['created'] > 0) {
            $this->activityLogService->log(
                ActivityLog::MODULE_PRODUCT,
                ActivityLog::ACTION_IMPORT,
                sprintf(
                    'Shopee XLSX import: %d products updated, %d created, %d skipped (plan limit), %d errors.',
                    $summary['updated'],
                    $summary['created'],
                    $summary['skipped_plan_limit'],
                    count($summary['errors'])
                )
            );
        }

        $report('done', $totalFamilies, $totalFamilies);

        return $summary;
    }

    /**
     * Merge the four sheets into one row-per-family structure keyed by Shopee
     * product_id, keeping every variation row as-is so multi-variation families
     * can be split into separate products.
     *
     * @param  array<int, array<string, mixed>>  $basic
     * @param  array<int, array<string, mixed>>  $media
     * @param  array<int, array<string, mixed>>  $sales
     * @return array<int, array<string, mixed>>
     */
    private function merge(array $basic, array $media, array $sales): array
    {
        $merged = [];

        foreach ($basic as $row) {
            $id = (string) ($row['et_title_product_id'] ?? '');
            if ($id === '') {
                continue;
            }

            $merged[$id] = [
                'product_id' => $id,
                'code' => trim((string) ($row['et_title_parent_sku'] ?? '')),
                'name' => trim((string) ($row['et_title_product_name'] ?? '')),
                'description' => trim((string) ($row['et_title_product_description'] ?? '')),
                'cover_url' => '',
                'category_path' => '',
                'variants' => [],
            ];
        }

        foreach ($media as $row) {
            $id = (string) ($row['et_title_product_id'] ?? '');
            if ($id === '' || ! isset($merged[$id])) {
                continue;
            }

            $cover = trim((string) ($row['ps_item_cover_image'] ?? ''));
            // Cover cell may hold multiple URLs; the first one is the product cover.
            if ($cover !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $cover);
                $merged[$id]['cover_url'] = trim((string) $lines[0]);
            }
            $merged[$id]['category_path'] = trim((string) ($row['et_title_product_category'] ?? ''));
        }

        foreach ($sales as $row) {
            $id = (string) ($row['et_title_product_id'] ?? '');
            if ($id === '' || ! isset($merged[$id])) {
                continue;
            }

            $merged[$id]['variants'][] = [
                'variation_id' => trim((string) ($row['et_title_variation_id'] ?? '')),
                'variation_name' => trim((string) ($row['et_title_variation_name'] ?? '')),
                'variation_sku' => trim((string) ($row['et_title_variation_sku'] ?? '')),
                'price' => $this->toNumber($row['et_title_variation_price'] ?? null),
                'stock' => $this->toNumber($row['et_title_variation_stock'] ?? null),
                'gtin' => trim((string) ($row['ps_gtin_code'] ?? '')),
            ];
        }

        return array_values($merged);
    }

    /**
     * Build the per-product targets for one Shopee family.
     *
     * Single variation (or no sales rows): one target — the parent code under the
     * plain basic-info name, exactly like a non-variation product.
     * Multiple variations: variant #1 keeps the parent code (family anchor, name
     * gets " - {variation name}"), variants #2..N get derived codes.
     *
     * @param  array<string, mixed>  $family
     * @return array<int, array{code: string, name: string, variant: array<string, mixed>|null}>
     */
    private function targetsFor(array $family, string $parentCode): array
    {
        $variants = $family['variants'];

        if (count($variants) <= 1) {
            return [[
                'code' => $parentCode,
                'name' => $family['name'],
                'variant' => $variants[0] ?? null,
            ]];
        }

        $targets = [];
        $usedCodes = [$parentCode];

        foreach ($variants as $i => $variant) {
            if ($i === 0) {
                $code = $parentCode;
            } else {
                $code = $this->deriveVariantCode($parentCode, $variant, $i, $usedCodes);
            }
            $usedCodes[] = $code;

            $targets[] = [
                'code' => $code,
                'name' => $this->variantName($family['name'], $variant, $parentCode),
                'variant' => $variant,
            ];
        }

        return $targets;
    }

    /**
     * Deterministic code for a sibling variant so re-imports match the same row.
     * Preference: Shopee child SKU → "{parent}-{slug(variation name)}" →
     * "{parent}-{variation_id}" → "{parent}-{index}".
     *
     * @param  array<string, mixed>  $variant
     * @param  array<int, string>  $usedCodes  codes already taken inside this family
     */
    private function deriveVariantCode(string $parentCode, array $variant, int $index, array $usedCodes): string
    {
        $sku = trim((string) ($variant['variation_sku'] ?? ''));
        if ($sku !== '') {
            if (! in_array($sku, $usedCodes, true)) {
                return $sku;
            }
        }

        $name = trim((string) ($variant['variation_name'] ?? ''));
        if ($name !== '') {
            $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($name)), '-');
            if ($slug !== '') {
                $candidate = $parentCode.'-'.mb_substr($slug, 0, 40);
                if (! in_array($candidate, $usedCodes, true)) {
                    return $candidate;
                }
            }
        }

        $variationId = trim((string) ($variant['variation_id'] ?? ''));
        if ($variationId !== '') {
            return $parentCode.'-'.$variationId;
        }

        return $parentCode.'-'.$index;
    }

    /**
     * Display name for a split variant: "{product name} - {variation name}".
     *
     * @param  array<string, mixed>  $variant
     */
    private function variantName(string $base, array $variant, string $fallback): string
    {
        $variationName = trim((string) ($variant['variation_name'] ?? ''));

        if ($base === '') {
            return $variationName !== '' ? $variationName : $fallback;
        }

        return $variationName !== '' ? "{$base} - {$variationName}" : $base;
    }

    /**
     * @param  array<string, mixed>  $target  from targetsFor(): code/name/variant
     * @param  array<string, mixed>  $family
     */
    private function updateProduct(Product $product, array $target, array $family, array &$summary): void
    {
        $data = [];
        $name = (string) $target['name'];

        if ($name !== '' && $name !== $product->name) {
            $data['name'] = $name;
        }

        if ($family['description'] !== '' && $family['description'] !== $product->description) {
            $data['description'] = $family['description'];
        }

        $variant = $target['variant'];
        $price = $variant['price'] ?? null;
        if ($price !== null && round($price, 2) !== round((float) $product->selling_price, 2)) {
            $data['selling_price'] = round($price, 2);
        }

        $gtin = trim((string) ($variant['gtin'] ?? ''));
        if ($gtin !== '' && $this->gtinIsAvailable($gtin, $product->id)) {
            $data['barcode'] = $gtin;
        }

        $cover = trim((string) ($family['cover_url'] ?? ''));
        if ($cover !== '' && $cover !== $product->cover_url) {
            $data['cover_url'] = $cover;
            // Drop a stale local cache from a *previous* cover URL so the
            // accessor shows the new remote image immediately; the queued
            // download replaces it with the matching file. Manual uploads are
            // left alone (the download job overwrites them as before).
            $oldName = (string) $product->getRawOriginal('image');
            $expectedPrefix = 'shopee_'.$product->id.'_'.Str::substr(sha1($cover), 0, 12).'.';
            if (Str::startsWith($oldName, 'shopee_') && ! Str::startsWith($oldName, $expectedPrefix)) {
                $data['image'] = null;
            }
        }

        if (! empty($data)) {
            $product->update($data);
        }
    }

    /**
     * @param  array<string, mixed>  $target  from targetsFor(): code/name/variant
     * @param  array<string, mixed>  $family
     */
    private function createProduct(array $target, array $family, ?int $parentId): Product
    {
        $unit = Unit::query()->first();
        if (! $unit) {
            throw new InvalidArgumentException('no unit available — create at least one unit first');
        }

        $supplier = Supplier::query()->first();
        if (! $supplier) {
            throw new InvalidArgumentException('no supplier available — create at least one supplier first');
        }

        $variant = $target['variant'];
        $price = $variant['price'] ?? null;
        if ($price === null) {
            // Never create with price 0 (free product in POS). The sales file is only
            // required for creation — update-only imports may omit it, but a new
            // product without price data is refused; the user sets a price manually,
            // then re-imports via the safe update path (never touches an existing price).
            throw new InvalidArgumentException('no price data — a sales file with prices is required to create new products');
        }

        $code = (string) $target['code'];
        $name = (string) $target['name'];

        $data = [
            'code' => $code,
            'name' => $name !== '' ? $name : $code,
            'description' => $family['description'],
            'price' => 0,
            'selling_price' => round($price, 2),
            'category_id' => $this->resolveCategoryId($family['category_path']),
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'parent_id' => $parentId,
            'cover_url' => trim((string) ($family['cover_url'] ?? '')) ?: null,
        ];

        $gtin = trim((string) ($variant['gtin'] ?? ''));
        if ($gtin !== '' && $this->gtinIsAvailable($gtin)) {
            $data['barcode'] = $gtin;
        }

        return Product::create($data);
    }

    /**
     * Set the absolute stock quantity in the main warehouse (same semantics as bulkUpdateStock).
     *
     * @param  array<string, mixed>  $target  from targetsFor(): code/name/variant
     */
    private function syncStock(Product $product, array $target, array &$summary): void
    {
        $stock = $target['variant']['stock'] ?? null;
        if ($stock === null) {
            return;
        }

        $warehouse = Warehouse::where('is_main', true)->first();
        if (! $warehouse) {
            $summary['stock_skipped']++;

            return;
        }

        $quantity = (int) $stock;
        if ($quantity < 0) {
            $summary['stock_skipped']++;

            return;
        }

        $stockRecord = ProductWarehouse::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 0]
        );

        if ((float) $stockRecord->quantity !== (float) $quantity) {
            $stockRecord->update(['quantity' => $quantity]);
        }
    }

    /**
     * Resolve a local category from the Shopee category path ("101798 - Home & Living/.../Pliers").
     * Falls back to a reusable "Shopee Import" category. Never overwrites existing mappings.
     */
    private function resolveCategoryId(string $categoryPath): int
    {
        $leaf = $categoryPath;
        if (str_contains($leaf, '/')) {
            $parts = explode('/', $leaf);
            $leaf = trim((string) end($parts));
        }
        // Strip "12345 - " prefix when present
        if (preg_match('/^\d+\s*-\s*(.+)$/', $leaf, $m)) {
            $leaf = trim($m[1]);
        }

        if ($leaf !== '') {
            $match = Categories::where('name', $leaf)->first();
            if ($match) {
                return $match->id;
            }
        }

        $fallback = Categories::firstOrCreate(
            ['name' => 'Shopee Import'],
            ['description' => 'Auto-created for products imported from Shopee XLSX files']
        );

        return $fallback->id;
    }

    /**
     * GTIN/barcode has a GLOBAL unique constraint — only assign when free.
     */
    private function gtinIsAvailable(string $gtin, ?int $exceptProductId = null): bool
    {
        $query = Product::withoutGlobalScopes()->where('barcode', $gtin);
        if ($exceptProductId !== null) {
            $query->where('id', '!=', $exceptProductId);
        }

        return ! $query->exists();
    }

    /**
     * @param  array<int, array{product_id: int, url: string, current_image: string|null}>  $covers
     * @param  array<int, string>  $errors  appended to when queueing fails
     * @return int number of queued image download jobs
     */
    private function queueCoverDownloads(array $covers, array &$errors): int
    {
        // Re-import fast path: a product whose stored image already belongs to
        // this cover URL needs no download — skipping keeps repeat imports from
        // flooding the queue with byte-identical fetches.
        $pending = [];
        foreach ($covers as $cover) {
            if ($this->coverAlreadyStored((int) $cover['product_id'], (string) $cover['url'], $cover['current_image'] ?? null)) {
                continue;
            }

            $pending[] = ['product_id' => $cover['product_id'], 'url' => $cover['url']];
        }

        if ($pending === []) {
            return 0;
        }

        $tenantId = app('currentTenant')?->id;
        if ($tenantId === null) {
            return 0;
        }

        $queued = 0;
        // Small batches keep each job well under the 300s job timeout even when
        // the CDN stalls on every request (15 items x 15s worst case = 225s).
        foreach (array_chunk($pending, 15) as $chunk) {
            try {
                DownloadShopeeCoverImages::dispatch($tenantId, $chunk);
                $queued += count($chunk);
            } catch (\Throwable $e) {
                // Never fail the whole import because the queue is unavailable —
                // the products are already committed; surface it in the summary.
                report($e);
                $errors[] = 'Cover images could not be queued (queue unavailable): '.$e->getMessage();

                break;
            }
        }

        return $queued;
    }

    /**
     * True when the product already stores the shopee_{id}_{sha1(url)} file
     * for this exact cover URL and the file is on disk.
     */
    private function coverAlreadyStored(int $productId, string $url, ?string $currentImage): bool
    {
        if ($currentImage === null || $currentImage === '') {
            return false;
        }

        $expectedPrefix = 'shopee_'.$productId.'_'.substr(sha1($url), 0, 12).'.';

        return str_starts_with($currentImage, $expectedPrefix)
            && Storage::disk('public')->exists('image/'.$currentImage);
    }

    private function toNumber(mixed $value): float|int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return null;
    }
}
