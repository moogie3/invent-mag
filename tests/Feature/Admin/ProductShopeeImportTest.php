<?php

namespace Tests\Feature\Admin;

use App\Jobs\DownloadShopeeCoverImages;
use App\Models\Categories;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Shopee\ShopeeImportService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row as SpoutRow;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductShopeeImportTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const BASIC_HEADERS = [
        'et_title_product_id', 'et_title_parent_sku', 'et_title_product_name',
        'et_title_product_description', 'et_title_reason',
    ];

    private const MEDIA_HEADERS = [
        'et_title_product_id', 'et_title_parent_sku', 'et_title_product_name',
        'et_title_product_category', 'ps_item_cover_image',
    ];

    private const SALES_HEADERS = [
        'et_title_product_id', 'et_title_product_name', 'et_title_variation_id',
        'et_title_variation_name', 'et_title_parent_sku', 'et_title_variation_sku',
        'et_title_variation_price', 'ps_gtin_code', 'et_title_variation_stock',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PlanSeeder::class);
        Storage::fake('public');
        Queue::fake();

        Warehouse::factory()->create(['is_main' => true]);
        Categories::factory()->create();
        Unit::factory()->create();
        Supplier::factory()->create();

        $this->assignPlan('enterprise');
    }

    private function assignPlan(string $slug): void
    {
        $plan = Plan::findBySlug($slug);
        $this->tenant->update([
            'plan_id' => $plan->id,
            'plan_status' => 'active',
            'plan_expires_at' => null,
            'trial_ends_at' => null,
        ]);
        $this->tenant->refresh();
        $this->tenant->makeCurrent();
    }

    /**
     * @param  array<int, array<int, mixed>>  $dataRows
     */
    private function makeXlsx(string $type, array $headers, array $dataRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'shopee_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(SpoutRow::fromValues($headers));
        $writer->addRow(SpoutRow::fromValues([$type, 'hash', '0', '1182502206', '{"search_condition":{}}']));
        $writer->addRow(SpoutRow::fromValues(array_map(fn ($h) => 'display_'.$h, $headers)));
        foreach ($dataRows as $row) {
            $writer->addRow(SpoutRow::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    private function uploadFrom(string $path, string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
    }

    private function runImport(array $paths): array
    {
        return app(ShopeeImportService::class)->import($paths);
    }

    #[Test]
    public function it_updates_an_existing_product_matched_by_code()
    {
        $product = Product::factory()->create([
            'code' => 'TL-0001',
            'name' => 'Old Name',
            'description' => 'Old description',
            'selling_price' => 10000,
        ]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['57907016200', 'TL-0001', 'Tang Potong 6 Inch', 'Deskripsi baru', ''],
        ]);
        $media = $this->makeXlsx('media_info', self::MEDIA_HEADERS, [
            ['57907016200', 'TL-0001', 'Tang Potong 6 Inch', '101798 - Home & Living/Tools/Pliers',
                'https://cf.shopee.co.id/file/abc123'],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['57907016200', 'Tang Potong 6 Inch', '375626392441', '', 'TL-0001', '', '60000', '8991234567890', '99'],
        ]);

        $summary = $this->runImport([
            'basic' => $basic,
            'media' => $media,
            'sales' => $sales,
        ]);

        $this->assertSame(1, $summary['updated']);
        $this->assertSame(0, $summary['created']);

        $product->refresh();
        $this->assertSame('Tang Potong 6 Inch', $product->name);
        $this->assertSame('Deskripsi baru', $product->description);
        $this->assertSame(60000.0, (float) $product->selling_price);
        $this->assertSame('8991234567890', $product->barcode);

        $warehouse = Warehouse::where('is_main', true)->first();
        $this->assertSame(99.0, (float) ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)->value('quantity'));

        Queue::assertPushed(DownloadShopeeCoverImages::class);
    }

    #[Test]
    public function it_creates_missing_products()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['111', 'NEW-001', 'Produk Baru', 'Deskripsi', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['111', 'Produk Baru', '1', '', 'NEW-001', '', '25000', '', '10'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales]);

        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, $summary['skipped_plan_limit']);

        $product = Product::where('code', 'NEW-001')->first();
        $this->assertNotNull($product);
        $this->assertSame('Produk Baru', $product->name);
        $this->assertSame(25000.0, (float) $product->selling_price);

        $warehouse = Warehouse::where('is_main', true)->first();
        $this->assertSame(10.0, (float) ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)->value('quantity'));
    }

    #[Test]
    public function it_caps_creations_at_the_plan_product_limit()
    {
        $starter = Plan::findBySlug('starter');
        $starter->update(['max_products' => 0]);
        $this->assignPlan('starter');

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['111', 'NEW-001', 'Produk Baru', 'Deskripsi', ''],
            ['112', 'NEW-002', 'Produk Baru 2', 'Deskripsi', ''],
        ]);

        $summary = $this->runImport(['basic' => $basic]);

        $this->assertSame(0, $summary['created']);
        $this->assertSame(2, $summary['skipped_plan_limit']);
        $this->assertNull(Product::where('code', 'NEW-001')->first());
    }

    #[Test]
    public function it_rejects_a_file_that_is_not_a_shopee_mass_update_export()
    {
        $fake = $this->makeXlsx('not_shopee_type', self::BASIC_HEADERS, [
            ['111', 'NEW-001', 'X', 'Y', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['111', 'X', '1', '', 'NEW-001', '', '1000', '', '1'],
        ]);

        $response = $this->postJson(route('product.shopee-import'), [
            'basic_file' => $this->uploadFrom($fake, 'basic.xlsx'),
            'sales_file' => $this->uploadFrom($sales, 'sales.xlsx'),
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    #[Test]
    public function the_route_accepts_imports_without_a_sales_file_for_existing_products()
    {
        $product = Product::factory()->create([
            'code' => 'UPD-001',
            'name' => 'Old Name',
            'selling_price' => 5000,
        ]);
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['888', 'UPD-001', 'Updated Name', 'new desc', ''],
        ]);

        $this->postJson(route('product.shopee-import'), [
            'basic_file' => $this->uploadFrom($basic, 'basic.xlsx'),
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.updated', 1)
            ->assertJsonPath('summary.created', 0);

        $product->refresh();
        $this->assertSame('Updated Name', $product->name);
        // Price and stock stay untouched without a sales file
        $this->assertSame(5000.0, (float) $product->selling_price);
    }

    #[Test]
    public function the_route_refuses_creating_new_products_without_a_sales_file()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['889', 'FRESH-001', 'Fresh Product', 'desc', ''],
        ]);

        $response = $this->postJson(route('product.shopee-import'), [
            'basic_file' => $this->uploadFrom($basic, 'basic.xlsx'),
        ]);

        $response->assertStatus(200)->assertJsonPath('summary.created', 0);
        $errors = $response->json('summary.errors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('sales file with prices is required', $errors[0]);
        $this->assertNull(Product::where('code', 'FRESH-001')->first());
    }

    #[Test]
    public function it_splits_products_with_different_variation_prices()
    {
        $product = Product::factory()->create([
            'code' => 'VAR-001',
            'name' => 'Var Product',
            'selling_price' => 50000,
        ]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['222', 'VAR-001', 'Var Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['222', 'Var Product', '1', 'Merah', 'VAR-001', '', '60000', '', '5'],
            ['222', 'Var Product', '2', 'Biru', 'VAR-001', '', '70000', '', '7'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales]);

        $this->assertSame(1, $summary['updated']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(1, $summary['split_families']);
        $this->assertArrayNotHasKey('price_skipped', $summary);

        // Anchor = variation 1: keeps the parent code, gets its own price/name
        $product->refresh();
        $this->assertSame('Var Product - Merah', $product->name);
        $this->assertSame(60000.0, (float) $product->selling_price);
        $this->assertNull($product->parent_id);

        // Sibling = variation 2: derived code, linked to the anchor, own price
        $sibling = Product::where('code', 'VAR-001-biru')->first();
        $this->assertNotNull($sibling);
        $this->assertSame('Var Product - Biru', $sibling->name);
        $this->assertSame(70000.0, (float) $sibling->selling_price);
        $this->assertSame($product->id, $sibling->parent_id);

        // Stock is per variation (not summed)
        $warehouse = Warehouse::where('is_main', true)->first();
        $this->assertSame(5.0, (float) ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertSame(7.0, (float) ProductWarehouse::where('product_id', $sibling->id)
            ->where('warehouse_id', $warehouse->id)->value('quantity'));
    }

    #[Test]
    public function shipping_rows_are_counted_but_not_imported()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['333', 'SHP-001', 'Shipping Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['333', 'Shipping Product', '1', '', 'SHP-001', '', '15000', '', '4'],
        ]);
        $shipping = $this->makeXlsx('shipping_info', [
            'et_title_product_id', 'et_title_product_weight',
        ], [
            ['333', '200'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales, 'shipping' => $shipping]);

        $this->assertSame(1, $summary['shipping_rows_ignored']);
        $this->assertSame(1, $summary['created']);
        $this->assertNotNull(Product::where('code', 'SHP-001')->first());
    }

    #[Test]
    public function it_creates_a_new_product_family_with_split_variants()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['666', 'MIX-001', 'Mixed Price Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['666', 'Mixed Price Product', '1', 'Merah', 'MIX-001', '', '60000', '', '5'],
            ['666', 'Mixed Price Product', '2', 'Biru', 'MIX-001', '', '70000', '', '7'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales]);

        $this->assertSame(2, $summary['created']);
        $this->assertSame(0, $summary['updated']);
        $this->assertSame(1, $summary['split_families']);
        $this->assertSame([], $summary['errors']);

        $anchor = Product::where('code', 'MIX-001')->first();
        $this->assertNotNull($anchor);
        $this->assertSame('Mixed Price Product - Merah', $anchor->name);
        $this->assertSame(60000.0, (float) $anchor->selling_price);
        $this->assertNull($anchor->parent_id);

        $sibling = Product::where('code', 'MIX-001-biru')->first();
        $this->assertNotNull($sibling);
        $this->assertSame('Mixed Price Product - Biru', $sibling->name);
        $this->assertSame(70000.0, (float) $sibling->selling_price);
        $this->assertSame($anchor->id, $sibling->parent_id);
    }

    #[Test]
    public function variant_codes_prefer_the_shopee_child_sku()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['776', 'SKU-001', 'SKU Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['776', 'SKU Product', '1', 'Merah', 'SKU-001', 'IGNORED-CHILD-SKU', '1000', '', '2'],
            ['776', 'SKU Product', '2', 'Biru', 'SKU-001', 'SKU-001-B', '2000', '', '3'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales]);

        $this->assertSame(2, $summary['created']);

        // Variant 1 always keeps the parent SKU Induk code (legacy match key),
        // even when the child SKU differs.
        $anchor = Product::where('code', 'SKU-001')->first();
        $this->assertNotNull($anchor);
        $this->assertNull(Product::where('code', 'IGNORED-CHILD-SKU')->first());

        // Variant 2 uses the Shopee child SKU as its code
        $sibling = Product::where('code', 'SKU-001-B')->first();
        $this->assertNotNull($sibling);
        $this->assertSame($anchor->id, $sibling->parent_id);
    }

    #[Test]
    public function split_import_is_idempotent_across_runs()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['996', 'FAM-001', 'Family Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['996', 'Family Product', '1', 'Merah', 'FAM-001', '', '1000', '', '1'],
            ['996', 'Family Product', '2', 'Biru', 'FAM-001', '', '2000', '', '2'],
        ]);

        $first = $this->runImport(['basic' => $basic, 'sales' => $sales]);
        $this->assertSame(2, $first['created']);
        $this->assertSame(2, Product::count());

        // Same files again: derived codes resolve to the same products
        $second = $this->runImport(['basic' => $basic, 'sales' => $sales]);
        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['updated']);
        $this->assertSame([], $second['errors']);
        $this->assertSame(2, Product::count());
    }

    #[Test]
    public function the_product_table_shows_the_variant_badge()
    {
        $this->user->assignRole('superuser');

        $anchor = Product::factory()->create([
            'code' => 'BADGE-001',
            'name' => 'Badge Anchor',
        ]);
        Product::factory()->create([
            'code' => 'BADGE-001-red',
            'name' => 'Badge Variant',
            'parent_id' => $anchor->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.product'))
            ->assertStatus(200)
            ->assertSee('Variant of BADGE-001');
    }

    #[Test]
    public function it_refuses_to_create_a_product_without_any_price_rows()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['777', 'NOPRICE-001', 'No Price Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['777', 'No Price Product', '1', '', 'NOPRICE-001', '', '', '', '3'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'sales' => $sales]);

        $this->assertSame(0, $summary['created']);
        $this->assertCount(1, $summary['errors']);
        $this->assertStringContainsString('no price data', $summary['errors'][0]);
        $this->assertStringContainsString('sales file with prices is required', $summary['errors'][0]);
        $this->assertNull(Product::where('code', 'NOPRICE-001')->first());
    }

    #[Test]
    public function the_import_route_requires_the_import_permission()
    {
        $userWithoutRole = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['444', 'X-1', 'X', 'Y', ''],
        ]);

        $this->actingAs($userWithoutRole)
            ->postJson(route('product.shopee-import'), [
                'basic_file' => $this->uploadFrom($basic, 'basic.xlsx'),
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function a_user_can_import_via_the_route()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['555', 'ROUTE-001', 'Via Route', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['555', 'Via Route', '1', '', 'ROUTE-001', '', '15000', '', '3'],
        ]);

        $response = $this->postJson(route('product.shopee-import'), [
            'basic_file' => $this->uploadFrom($basic, 'basic.xlsx'),
            'sales_file' => $this->uploadFrom($sales, 'sales.xlsx'),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.created', 1);
    }

    #[Test]
    public function the_progress_endpoint_reports_import_progress()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['556', 'PROG-001', 'Progress Product', 'desc', ''],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['556', 'Progress Product', '1', '', 'PROG-001', '', '15000', '', '3'],
        ]);

        $this->postJson(route('product.shopee-import'), [
            'basic_file' => $this->uploadFrom($basic, 'basic.xlsx'),
            'sales_file' => $this->uploadFrom($sales, 'sales.xlsx'),
            'progress_token' => 'test-token-123',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->getJson(route('product.shopee-import.progress', ['token' => 'test-token-123']))
            ->assertStatus(200)
            ->assertJsonPath('phase', 'done')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('processed', 1)
            ->assertJsonPath('queue_pending', 0)
            ->assertJsonStructure(['phase', 'processed', 'total', 'updated_at', 'queue_pending']);
    }

    #[Test]
    public function the_progress_endpoint_rejects_invalid_tokens()
    {
        $this->getJson(route('product.shopee-import.progress', ['token' => 'no spaces allowed']))
            ->assertStatus(422);
    }

    #[Test]
    public function covers_that_are_already_stored_are_not_queued_again()
    {
        $product = Product::factory()->create([
            'code' => 'CVR-001',
            'name' => 'Cover Product',
        ]);

        $url = 'https://cf.shopee.co.id/file/abc123';
        $stored = 'shopee_'.$product->id.'_'.substr(sha1($url), 0, 12).'.jpg';
        Storage::disk('public')->put('image/'.$stored, 'already-here');
        $product->update(['image' => $stored]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['888', 'CVR-001', 'Cover Product', 'desc', ''],
        ]);
        $media = $this->makeXlsx('media_info', self::MEDIA_HEADERS, [
            ['888', 'CVR-001', 'Cover Product', '101798 - Home & Living/Tools', $url],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['888', 'Cover Product', '1', '', 'CVR-001', '', '1000', '', '5'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'media' => $media, 'sales' => $sales]);

        $this->assertSame(1, $summary['updated']);
        $this->assertSame(0, $summary['images_queued']);
        Queue::assertNotPushed(DownloadShopeeCoverImages::class);
    }

    #[Test]
    public function it_persists_the_remote_cover_url_on_create_so_the_image_shows_immediately()
    {
        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['777', 'NEW-002', 'Produk Baru 2', 'Deskripsi', ''],
        ]);
        $media = $this->makeXlsx('media_info', self::MEDIA_HEADERS, [
            ['777', 'NEW-002', 'Produk Baru 2', '101798 - Home & Living/Tools',
                'https://cf.shopee.co.id/file/cover-new'],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['777', 'Produk Baru 2', '1', '', 'NEW-002', '', '25000', '', '10'],
        ]);

        $summary = $this->runImport(['basic' => $basic, 'media' => $media, 'sales' => $sales]);
        $this->assertSame(1, $summary['created']);

        $product = Product::where('code', 'NEW-002')->firstOrFail();
        $this->assertSame('https://cf.shopee.co.id/file/cover-new', $product->cover_url);
        // No local file yet (queue faked) — accessor falls back to the remote cover.
        $this->assertNull($product->getRawOriginal('image'));
        $this->assertSame('https://cf.shopee.co.id/file/cover-new', $product->image);
        $this->assertSame(1, $summary['images_queued']);
    }

    #[Test]
    public function a_changed_cover_url_resets_the_stale_shopee_cache_and_shows_the_new_remote_image()
    {
        $product = Product::factory()->create([
            'code' => 'TL-002',
            'name' => 'Old Cover Product',
        ]);
        $stale = 'shopee_'.$product->id.'_000000000000.jpg';
        Storage::disk('public')->put('image/'.$stale, 'old-bytes');
        $product->update(['image' => $stale]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['778', 'TL-002', 'Old Cover Product', 'desc', ''],
        ]);
        $media = $this->makeXlsx('media_info', self::MEDIA_HEADERS, [
            ['778', 'TL-002', 'Old Cover Product', '101798 - Home & Living/Tools',
                'https://cf.shopee.co.id/file/brand-new-cover'],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['778', 'Old Cover Product', '1', '', 'TL-002', '', '5000', '', '2'],
        ]);

        $this->runImport(['basic' => $basic, 'media' => $media, 'sales' => $sales]);

        $product->refresh();
        $this->assertSame('https://cf.shopee.co.id/file/brand-new-cover', $product->cover_url);
        $this->assertNull($product->getRawOriginal('image'));
        $this->assertSame('https://cf.shopee.co.id/file/brand-new-cover', $product->image);
    }

    #[Test]
    public function manual_uploads_are_left_visible_until_the_download_replaces_them()
    {
        $product = Product::factory()->create([
            'code' => 'TL-003',
            'name' => 'Manual Image Product',
            'image' => 'manual-upload.jpg',
        ]);

        $basic = $this->makeXlsx('basic_info', self::BASIC_HEADERS, [
            ['779', 'TL-003', 'Manual Image Product', 'desc', ''],
        ]);
        $media = $this->makeXlsx('media_info', self::MEDIA_HEADERS, [
            ['779', 'TL-003', 'Manual Image Product', '101798 - Home & Living/Tools',
                'https://cf.shopee.co.id/file/some-cover'],
        ]);
        $sales = $this->makeXlsx('sales_info', self::SALES_HEADERS, [
            ['779', 'Manual Image Product', '1', '', 'TL-003', '', '7000', '', '4'],
        ]);

        $this->runImport(['basic' => $basic, 'media' => $media, 'sales' => $sales]);

        $product->refresh();
        $this->assertSame('https://cf.shopee.co.id/file/some-cover', $product->cover_url);
        $this->assertSame('manual-upload.jpg', $product->getRawOriginal('image'));
    }
}
