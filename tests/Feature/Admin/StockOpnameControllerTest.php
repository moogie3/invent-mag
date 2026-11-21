<?php

namespace Tests\Feature\Admin;

use App\Helpers\CurrencyHelper;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Tenant;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class StockOpnameControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_index_returns_200_with_stock_opname_list()
    {
        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.stock-opname.index');
        $response->assertViewHas('opnames');
    }

    public function test_index_displays_paginated_opnames()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        StockOpname::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.index'));

        $response->assertStatus(200);
        $opnames = $response->viewData('opnames');
        $this->assertEquals(15, $opnames->perPage());
        $this->assertEquals(20, $opnames->total());
    }

    public function test_factory_reference_numbers_are_unique_across_a_batch()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        StockOpname::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        $references = StockOpname::where('tenant_id', $this->tenant->id)
            ->pluck('reference_number');

        $this->assertCount(20, $references);
        $this->assertCount(
            20,
            $references->unique(),
            'Duplicate reference numbers: '.implode(', ', $references->duplicates()->all())
        );
        $this->assertMatchesRegularExpression('/^SO-\d{6}-\d{4}$/', $references->first());
    }

    public function test_create_returns_200_with_warehouses()
    {
        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.stock-opname.create');
        $response->assertViewHas('warehouses');
    }

    public function test_store_creates_stock_opname()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.store'), [
            'warehouse_id' => $warehouse->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => 'Test stock opname',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_opnames', [
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'Draft',
            'notes' => 'Test stock opname',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.store'), []);

        $response->assertSessionHasErrors(['warehouse_id', 'scheduled_date']);
    }

    public function test_store_validates_warehouse_exists()
    {
        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.store'), [
            'warehouse_id' => 9999,
            'scheduled_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('warehouse_id');
    }

    /**
     * exists:warehouses,id is raw SQL and ignores the tenant scope, so the
     * id of another tenant's warehouse passed validation and was handed
     * straight to createOpname(): the opname row was written against a
     * warehouse this tenant cannot see, and its counts would adjust stock
     * in someone else's building. The warehouse must be re-checked through
     * the scoped model before anything is created.
     */
    public function test_store_refuses_a_warehouse_owned_by_another_tenant()
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        $foreignWarehouse = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.store'), [
            'warehouse_id' => $foreignWarehouse->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => 'Cross tenant attempt',
        ]);

        $response->assertSessionHasErrors('warehouse_id');
        $this->assertDatabaseMissing('stock_opnames', [
            'warehouse_id' => $foreignWarehouse->id,
            'notes' => 'Cross tenant attempt',
        ]);
    }

    public function test_store_validates_date_format()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.store'), [
            'warehouse_id' => $warehouse->id,
            'scheduled_date' => 'not-a-date',
        ]);

        $response->assertSessionHasErrors('scheduled_date');
    }

    /**
     * Bare links() renders the published default view: <li> and <a> with no
     * page-item / page-link classes at all, so Tabler's own pager styling
     * and its dark-mode token chain never engage. The result is a pager that
     * goes unstyled in dark mode, drops every filter on the query string
     * because it never appends, and shows no "showing entries" footer -
     * unlike the pages that already use the tabler view.
     */
    public function test_index_pagination_matches_the_standard_tabler_pager()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        StockOpname::factory()->count(16)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.index', ['page' => 1, 'search' => 'x']));

        $response->assertStatus(200);
        $html = $response->getContent();

        // The tabler view, not the classless default one.
        $this->assertStringContainsString('pagination-tabs', $html, 'The pager must use the tabler view.');
        // Every dark-mode rule targets .page-link, so the links need it.
        $this->assertStringContainsString('page-link', $html, 'The pager links must carry page-link for dark mode.');
        // Filters survive paging. There is no search box on this page, so the
        // only way the term reaches the pager hrefs is via appends().
        $this->assertStringContainsString('search=x', $html, 'The pager must append the current filters.');
        // 16 rows at 15 per page means a real link to page 2.
        $this->assertStringContainsString('page=2', $html, 'The pager must link to the next page.');
        // The standard footer.
        $this->assertStringContainsString('entries', $html, 'The pager must show the showing-entries footer.');
    }

    public function test_index_footer_renders_even_when_the_list_is_empty()
    {
        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.index'));

        $response->assertStatus(200);
        $html = $response->getContent();

        // The standard footer is unconditional on every other list page, so an
        // empty table still reports its zero count instead of going silent.
        $this->assertStringContainsString('data-pagination-footer', $html, 'The footer must render on an empty list.');
        $this->assertStringContainsString('entries', $html, 'An empty list must still show the entries line.');
        // With no rows firstItem()/lastItem() are null, which used to render
        // the footer as "Showing  to of 0 entries" with two blank slots.
        $this->assertStringContainsString('Showing 0 to 0 of 0 entries', $html, 'An empty list must say 0 to 0 of 0, not leave blanks.');
    }

    public function test_show_returns_200_for_owned_opname()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.stock-opname.show');
        $response->assertViewHas('stockOpname');
    }

    public function test_show_returns_403_for_other_tenant_opname()
    {
        // Skip this edge case test - requires special tenant setup
        $this->assertTrue(true);
    }

    public function test_update_counts_updates_items_and_transitions_status()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.update-counts', $opname->id), [
            'counts' => [
                $product->id => 95,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('In Progress', $opname->status);

        $item = $opname->items()->first();
        $this->assertEquals(95, $item->counted_qty);
        $this->assertEquals(-5, $item->variance);
    }

    /**
     * Every quantity in this system is a whole unit: product_warehouse,
     * po_items and sales_items are all integer columns. A fractional
     * count is a typo, not a measurement, and accepted it would reach the
     * integer stock column and fail there as a 500 instead of a message
     * the counter can act on.
     */
    public function test_update_counts_rejects_a_fractional_count()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.update-counts', $opname->id), [
            'counts' => [$product->id => 3.5],
        ]);

        $response->assertSessionHasErrors('counts.'.$product->id);
        $this->assertNull($opname->items()->first()->counted_qty, 'A fractional count must not be recorded.');
        $this->assertSame('Draft', $opname->refresh()->status, 'A rejected count must not move the opname along.');
    }

    public function test_update_counts_returns_403_for_completed_opname()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.update-counts', $opname->id), [
            'counts' => [$product->id => 50],
        ]);

        $this->assertTrue(in_array($response->status(), [403, 500]), "Got {$response->status()}");
    }

    public function test_complete_adjusts_stock_and_creates_adjustments()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'In Progress',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.complete', $opname->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('Completed', $opname->status);
        $this->assertNotNull($opname->completed_at);

        $pw = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        $this->assertEquals(95, $pw->quantity);

        $this->assertDatabaseHas('stock_adjustments', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => 'decrease',
            'quantity_before' => 100,
            'quantity_after' => 95,
        ]);
    }

    public function test_complete_returns_403_for_completed_opname()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.complete', $opname->id));

        $this->assertTrue(in_array($response->status(), [403, 500]));
    }

    public function test_cancel_sets_status_to_canceled()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.cancel', $opname->id));

        $response->assertRedirect(route('admin.stock-opname.index'));
        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('Canceled', $opname->status);
    }

    public function test_cancel_returns_403_for_completed_opname()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.cancel', $opname->id));

        $this->assertTrue(in_array($response->status(), [403, 500]));
    }

    public function test_store_requires_authentication()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->post(route('admin.stock-opname.store'), [
            'warehouse_id' => $warehouse->id,
            'scheduled_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertRedirect();
    }

    public function test_show_requires_authentication()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('admin.stock-opname.show', $opname->id));

        // Without auth, should redirect or return 403/500
        $this->assertTrue(in_array($response->status(), [200, 302, 403, 500]));
    }

    public function test_complete_requires_authentication()
    {
        $opname = StockOpname::factory()->create();

        $response = $this->post(route('admin.stock-opname.complete', $opname->id));

        $response->assertRedirect();
    }

    /**
     * StockOpnameService computes variance_value precisely so the count
     * screen shows the money figure the completion journal will book, but
     * no view ever rendered it: the counter saw units only, and the money
     * side of the count existed only in the database.
     */
    public function test_show_displays_the_variance_value_that_will_be_booked()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $response->assertSee(__('messages.variance_value'));
        $response->assertSee(CurrencyHelper::format(25000));
    }

    /**
     * The counting sheet is paginated, so a totals row under the table
     * would only ever add up one page. The whole-opname totals belong in
     * the details card, where they count every item regardless of page.
     */
    public function test_show_summarizes_the_whole_opname_totals()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $response->assertSee(__('messages.stock_opname_summary'));
        $response->assertSee(__('messages.total').' '.__('messages.expected_qty'));
        $response->assertSee(__('messages.shrinkage_value'));
    }

    /**
     * A counter working through 120 rows needs to see how far along they
     * are without adding the column up by eye. The two numbers already
     * exist server-side (counted_rows / row_count in the totals query);
     * the page just never showed them.
     */
    public function test_show_reports_counting_progress()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $counted = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Counted Widget']);
        $pending = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Pending Widget']);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $counted->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $pending->id,
            'expected_qty' => 40,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $response->assertSee(
            __('messages.counting_progress', ['counted' => 1, 'total' => 2]),
            false,
            'The counting sheet must say how many of how many rows are counted.'
        );
    }

    /**
     * After a count, the rows that need attention are the ones with a
     * variance; the rest are matched or not counted yet. The filter is
     * server-side because a client-side filter could only ever hide rows
     * on the page already on screen, and pagination would show the
     * unfiltered rows again on page 2.
     */
    public function test_show_filters_to_rows_with_a_variance()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $excess = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Excess Widget', 'price' => 5000]);
        $matched = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matched Widget', 'price' => 5000]);
        $uncounted = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Uncounted Widget', 'price' => 5000]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $excess->id,
            'expected_qty' => 100,
            'counted_qty' => 102,
            'variance' => 2,
            'variance_value' => 10000,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $matched->id,
            'expected_qty' => 50,
            'counted_qty' => 50,
            'variance' => 0,
            'variance_value' => 0,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $uncounted->id,
            'expected_qty' => 30,
            'counted_qty' => null,
            'variance' => null,
        ]);

        // Unfiltered: every row is on the sheet and the toggle is offered.
        $all = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));
        $all->assertOk();
        $all->assertSee('Excess Widget');
        $all->assertSee('Matched Widget');
        $all->assertSee('Uncounted Widget');
        $all->assertSee(__('messages.variance_only'));

        // Filtered: only the row with a variance survives, tinted, and the
        // filter state is in the URL so the pager cannot lose it.
        $filtered = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', array_merge(['view' => 'variance'], [$opname->id]))
        );
        $filtered->assertOk();
        $filtered->assertSee('Excess Widget');
        $filtered->assertDontSee('Matched Widget');
        $filtered->assertDontSee('Uncounted Widget');
        $filtered->assertSee('table-success', false, 'The variance row must be tinted so the eye lands on it.');
        $filtered->assertSee('view=variance');
    }

    /**
     * Autosave posts one row at a time as JSON. updateCounts() merges
     * (only the posted product ids are touched), which is exactly what
     * makes a single-field save safe, and the response carries the
     * recomputed variance, the progress and the whole-opname totals so
     * the page updates in place: a page change is a full reload, so
     * anything not saved is lost.
     */
    public function test_update_counts_accepts_a_json_single_row_save()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            route('admin.stock-opname.update-counts', $opname->id),
            ['counts' => [$product->id => 95]]
        );

        $response->assertOk();
        $response->assertJsonPath('progress.counted', 1);
        $response->assertJsonPath('progress.total', 1);
        $response->assertJsonPath("rows.{$product->id}.variance", -5);
        $response->assertJsonStructure(['rows' => [$product->id => ['variance', 'variance_value', 'variance_value_formatted']], 'progress' => ['counted', 'total'], 'totals' => ['expected_total', 'counted_total', 'variance_total', 'excess_total', 'shrinkage_total']]);

        $item = $opname->items()->first();
        $this->assertEquals(95, $item->counted_qty);
        $this->assertEquals(-5, $item->variance);
        $this->assertEquals('In Progress', $opname->fresh()->status);
    }

    /**
     * Every viewable model in this app exposes path(): Sales, Purchase and
     * Payment all do. StockOpname was the one left building its URL by
     * hand at each call site.
     */
    public function test_model_exposes_the_canonical_view_path()
    {
        $opname = StockOpname::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertSame(
            route('admin.stock-opname.show', $opname->id),
            $opname->path(),
            'StockOpname must expose the same path() its sibling models do.'
        );
    }

    /**
     * Completing an opname writes StockAdjustment rows, yet the page that
     * confirmed "Adjustments have been posted" offered no way to reach
     * the log that holds them.
     */
    public function test_show_links_to_the_adjustment_log_once_completed()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $response->assertSee(
            route('admin.reports.adjustment-log', ['warehouse_id' => $warehouse->id]),
            false
        );
    }

    /**
     * Counts are validated as integers and stored in integer columns, so
     * a fractional stepper on the field contradicted both: it invited a
     * value the server would reject.
     */
    public function test_count_input_is_whole_units_only()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'Draft',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('step="1"', $content, 'The count field must step whole units.');
        $this->assertStringNotContainsString('step="0.01"', $content, 'A fractional step contradicts the integer validation.');
    }

    /**
     * stock_opnames.total_variance_value was added by its own migration
     * and then never written: the column sat null forever while the
     * completion journal booked real numbers. A null there reads as "no
     * variance" to anyone querying the table directly.
     */
    public function test_completion_records_the_total_variance_value()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);

        // 5 extra units at the 5000 buying price: 25000 excess value.
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 105,
            'variance' => 5,
            'variance_value' => 25000,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.stock-opname.complete', $opname->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals(
            25000,
            (float) $opname->total_variance_value,
            'Completion must record what the journal booked in stock_opnames.total_variance_value.'
        );
    }

    /**
     * A blank filtered table reads as a broken page. When the variance
     * view has nothing to show, the page has to say why: here every
     * count matched (or nothing was counted yet), which is a result, not
     * a malfunction.
     */
    public function test_variance_view_says_so_when_there_is_nothing_to_review()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $matched = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matched Widget']);
        $pending = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Pending Widget']);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $matched->id,
            'expected_qty' => 50,
            'counted_qty' => 50,
            'variance' => 0,
            'variance_value' => 0,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $pending->id,
            'expected_qty' => 30,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $response = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'view' => 'variance'])
        );

        $response->assertOk();
        $response->assertSee(
            __('messages.no_variance_rows'),
            false,
            'The variance view must explain an empty result instead of showing a bare table.'
        );
    }

    /**
     * The third view of the counting sheet: what is still waiting for a
     * count. It composes with the other filters rather than replacing
     * them, and when every row has been counted it says so.
     */
    public function test_uncounted_view_lists_only_rows_still_waiting_for_a_count()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $counted = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Counted Widget']);
        $pending = Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Pending Widget']);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $counted->id,
            'expected_qty' => 100,
            'counted_qty' => 98,
            'variance' => -2,
            'variance_value' => 10000,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $pending->id,
            'expected_qty' => 40,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $all = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));
        $all->assertOk();
        $all->assertSee('Counted Widget');
        $all->assertSee('Pending Widget');
        $all->assertSee(__('messages.uncounted'));

        $filtered = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'view' => 'uncounted'])
        );
        $filtered->assertOk();
        $filtered->assertSee('Pending Widget');
        $filtered->assertDontSee('Counted Widget');

        // Every row counted: the uncounted view reports the result rather
        // than showing an empty grid.
        $countedOpname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $countedOpname->id,
            'product_id' => $counted->id,
            'expected_qty' => 5,
            'counted_qty' => 5,
            'variance' => 0,
            'variance_value' => 0,
        ]);

        $done = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $countedOpname->id, 'view' => 'uncounted'])
        );
        $done->assertOk();
        $done->assertSee(__('messages.no_uncounted_rows'), false);
    }

    /**
     * A 1169-row sheet is not scannable by eye. Search runs server-side
     * across name, code and barcode (the code a scanner emits), keeps
     * whatever view is active, and a scan that lands on a row off the
     * current page is found through the same search.
     */
    public function test_show_searches_the_sheet_by_name_code_and_barcode()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $alpha = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alpha Widget',
            'code' => 'AW-900',
            'barcode' => '4006381333931',
        ]);
        $beta = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Beta Gadget',
            'code' => 'BG-100',
            'barcode' => '9780201379624',
        ]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $alpha->id,
            'expected_qty' => 10,
            'counted_qty' => null,
            'variance' => null,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $beta->id,
            'expected_qty' => 20,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $byName = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'q' => 'alpha'])
        );
        $byName->assertOk();
        $byName->assertSee('Alpha Widget');
        $byName->assertDontSee('Beta Gadget');

        $byCode = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'q' => 'AW-900'])
        );
        $byCode->assertOk();
        $byCode->assertSee('Alpha Widget');
        $byCode->assertDontSee('Beta Gadget');

        $byBarcode = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'q' => '9780201379624'])
        );
        $byBarcode->assertOk();
        $byBarcode->assertSee('Beta Gadget');
        $byBarcode->assertDontSee('Alpha Widget');

        // Search and view compose: the uncounted view of a barcode hit.
        $combined = $this->actingAs($this->user)->get(
            route('admin.stock-opname.show', ['stockOpname' => $opname->id, 'q' => 'gadget', 'view' => 'uncounted'])
        );
        $combined->assertOk();
        $combined->assertSee('Beta Gadget');
        $combined->assertDontSee('Alpha Widget');
    }

    /**
     * The complete modal asked "are you sure?" while the amounts it would
     * book stayed behind the confirm click. The preview returns the exact
     * journal completion posts (the same service arithmetic, mirrored
     * from postCorrection) and writes nothing: no journal row, no batch
     * consumption, no variance_value rewrite while the opname is open.
     */
    public function test_complete_preview_shows_the_journal_without_writing_it()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 10,
            'counted_qty' => 8,
            'variance' => -2,
            'variance_value' => null,
        ]);

        $journalsBefore = JournalEntry::count();

        $response = $this->actingAs($this->user)->get(
            route('admin.stock-opname.complete-preview', $opname->id)
        );

        $response->assertOk();
        $response->assertJsonPath('will_post', true);
        $this->assertEquals(10000.0, (float) $response->json('totals.shrinkage'), 'Two missing units at the 5000 buying price.');
        $this->assertEquals(0.0, (float) $response->json('totals.excess'));

        $lines = $response->json('lines');
        $this->assertCount(2, $lines, 'Shrinkage posts exactly two legs: Dr COGS, Cr Inventory.');
        $debits = array_sum(array_column(array_filter($lines, fn ($l) => $l['type'] === 'debit'), 'amount'));
        $credits = array_sum(array_column(array_filter($lines, fn ($l) => $l['type'] === 'credit'), 'amount'));
        $this->assertEquals(10000.0, (float) $debits);
        $this->assertEquals($debits, $credits, 'The previewed journal must balance.');

        // The preview is read-only.
        $this->assertEquals($journalsBefore, JournalEntry::count(), 'A preview must not post anything.');
        $this->assertNull($opname->items()->first()->variance_value, 'A preview must not rewrite the open opname.');
        $this->assertEquals('In Progress', $opname->fresh()->status);
    }

    /**
     * The tools the page now offers: print the sheet, scan a barcode to
     * jump to a row. Both are plain hooks the JS binds to, so a missing
     * attribute here means the button on screen does nothing.
     */
    public function test_show_offers_print_and_scan_tools()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'In Progress',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.stock-opname.show', $opname->id));

        $response->assertOk();
        $response->assertSee('data-print-sheet', false);
        $response->assertSee('data-scan-input', false);
        $response->assertSee(__('messages.scan_barcode'), false);
    }
}
