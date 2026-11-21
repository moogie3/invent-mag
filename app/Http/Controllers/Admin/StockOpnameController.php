<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Warehouse;
use App\Services\StockOpnameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    protected StockOpnameService $stockOpnameService;

    public function __construct(StockOpnameService $stockOpnameService)
    {
        $this->stockOpnameService = $stockOpnameService;
    }

    public function index()
    {
        $opnames = StockOpname::with('warehouse', 'createdBy.roles')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.stock-opname.index', compact('opnames'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();

        return view('admin.stock-opname.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // exists: is raw SQL and ignores the tenant scope, so it would accept
        // another tenant's warehouse id. Re-resolve through the scoped model
        // before anything is written: a cross-tenant id reads back as null
        // and is reported on the field, exactly like a missing one.
        $warehouse = Warehouse::find($request->warehouse_id);
        if (! $warehouse) {
            return back()->withInput()->withErrors(['warehouse_id' => 'The selected warehouse is invalid.']);
        }

        try {
            $opname = $this->stockOpnameService->createOpname(
                $warehouse->id,
                $request->scheduled_date,
                $request->notes
            );

            return redirect()->route('admin.stock-opname.show', $opname->id)
                ->with('success', 'Stock Opname created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $user = Auth::user();
        if (! $user || $stockOpname->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $stockOpname->load('warehouse', 'createdBy.roles');

        // One whitelist decides the view: anything unknown (a stale link,
        // a typo) falls back to the full sheet instead of an empty page.
        // Server-side, because a client-side filter could only ever hide
        // rows on the page already on screen, and the next pager link
        // would bring the hidden rows back.
        $view = request()->query('view');
        $view = in_array($view, ['uncounted', 'variance'], true) ? $view : 'all';

        // Search runs across the product a row points at: name, code and
        // barcode (what a scanner emits), so a scan that lands off the
        // current page is still found through the same box.
        $search = trim((string) request()->query('q', ''));

        $items = $stockOpname->items()
            ->with('product')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                });
            })
            ->when($view === 'uncounted', function ($query) {
                $query->whereNull('counted_qty');
            })
            ->when($view === 'variance', function ($query) {
                $query->whereNotNull('variance')->where('variance', '!=', 0);
            })
            ->paginate(50);

        $totals = $this->totalsFor($stockOpname);

        // Every toggle link drops page and view first: switching views
        // always lands on page 1, while the other filters (search) survive
        // the switch.
        $baseQuery = request()->query();
        unset($baseQuery['page'], $baseQuery['view']);

        $allUrl = route('admin.stock-opname.show', array_merge([$stockOpname->id], $baseQuery));
        $uncountedUrl = route('admin.stock-opname.show', array_merge([$stockOpname->id], $baseQuery, ['view' => 'uncounted']));
        $varianceUrl = route('admin.stock-opname.show', array_merge([$stockOpname->id], $baseQuery, ['view' => 'variance']));

        return view('admin.stock-opname.show', compact('stockOpname', 'items', 'totals', 'view', 'search', 'allUrl', 'uncountedUrl', 'varianceUrl'));
    }

    /**
     * The journal completion would post, as JSON for the confirm modal.
     *
     * Read-only by contract: the preview runs the same arithmetic as
     * completeOpname() with every write stripped out, so what the modal
     * shows is what the confirm click books, and clicking around cannot
     * move stock or the GL.
     */
    public function completePreview(StockOpname $stockOpname)
    {
        $user = Auth::user();
        if (! $user || $stockOpname->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        return response()->json($this->stockOpnameService->previewCompletion($stockOpname));
    }

    /**
     * One aggregate over every item, not just the page on screen: the
     * counting sheet paginates, so a footer sum would quietly describe
     * 50 rows while claiming to describe the opname. counted_rows is
     * what separates "counted and matched" from "not counted yet",
     * which both read as zero otherwise. Shared by show() and the
     * autosave JSON so the page and the response cannot disagree.
     */
    private function totalsFor(StockOpname $stockOpname): StockOpnameItem
    {
        return StockOpnameItem::where('stock_opname_id', $stockOpname->id)
            ->selectRaw('
                COUNT(*) as row_count,
                COALESCE(SUM(expected_qty), 0) as expected_total,
                COALESCE(SUM(counted_qty), 0) as counted_total,
                COALESCE(SUM(variance), 0) as variance_total,
                COALESCE(SUM(CASE WHEN variance > 0 THEN variance_value ELSE 0 END), 0) as excess_total,
                COALESCE(SUM(CASE WHEN variance < 0 THEN variance_value ELSE 0 END), 0) as shrinkage_total,
                COUNT(counted_qty) as counted_rows
            ')
            ->first();
    }

    public function updateCounts(Request $request, StockOpname $stockOpname)
    {
        $user = Auth::user();
        if (! $user || $stockOpname->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        if ($stockOpname->status === 'Completed') {
            abort(403);
        }

        if ($stockOpname->status === 'Canceled') {
            abort(403);
        }

        $request->validate([
            'counts' => 'required|array',
            // Whole units only: every quantity column in the schema is an
            // integer, so a fractional count is a typo that would otherwise
            // surface as a database error instead of a validation message.
            'counts.*' => 'nullable|integer|min:0',
        ]);

        try {
            $this->stockOpnameService->updateCounts($stockOpname, $request->counts);

            // The autosave posts one row as JSON and repaints in place: a
            // page change is a full reload, so anything not saved is gone.
            // The browser form keeps its redirect.
            if ($request->wantsJson()) {
                return response()->json($this->autosavePayload($stockOpname, $request->counts));
            }

            return redirect()->route('admin.stock-opname.show', $stockOpname->id)
                ->with('success', 'Counts updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * What one autosave round trip returns: the rows just written (so the
     * page repaints variance and money in place), the progress, and the
     * whole-opname totals from the same aggregate show() renders. Money
     * is carried raw and formatted: the sidebar prints formatted strings,
     * the raw values stay available for anything that computes.
     */
    private function autosavePayload(StockOpname $stockOpname, array $counts): array
    {
        $rows = [];

        foreach (array_keys($counts) as $productId) {
            $item = StockOpnameItem::where('stock_opname_id', $stockOpname->id)
                ->where('product_id', $productId)
                ->first();

            if (! $item) {
                continue;
            }

            $rows[$productId] = [
                'variance' => $item->variance === null ? null : (int) $item->variance,
                'variance_value' => $item->variance_value === null ? null : (float) $item->variance_value,
                'variance_value_formatted' => $item->variance_value === null ? null : CurrencyHelper::format($item->variance_value),
            ];
        }

        $totals = $this->totalsFor($stockOpname);

        return [
            'rows' => $rows,
            'progress' => [
                'counted' => (int) $totals->counted_rows,
                'total' => (int) $totals->row_count,
            ],
            'totals' => [
                'expected_total' => (int) $totals->expected_total,
                'counted_total' => (int) $totals->counted_total,
                'variance_total' => (int) $totals->variance_total,
                'excess_total' => (float) $totals->excess_total,
                'shrinkage_total' => (float) $totals->shrinkage_total,
                'excess_total_formatted' => CurrencyHelper::format($totals->excess_total),
                'shrinkage_total_formatted' => CurrencyHelper::format($totals->shrinkage_total),
            ],
        ];
    }

    public function complete(StockOpname $stockOpname)
    {
        $user = Auth::user();
        if (! $user || $stockOpname->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        if ($stockOpname->status === 'Completed') {
            abort(403);
        }

        if ($stockOpname->status === 'Canceled') {
            abort(403);
        }

        try {
            $this->stockOpnameService->completeOpname($stockOpname);

            return redirect()->route('admin.stock-opname.show', $stockOpname->id)
                ->with('success', 'Stock Opname completed. Adjustments have been posted.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(StockOpname $stockOpname)
    {
        $user = Auth::user();
        if (! $user || $stockOpname->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        if ($stockOpname->status === 'Completed') {
            abort(403);
        }

        $stockOpname->update(['status' => 'Canceled']);

        return redirect()->route('admin.stock-opname.index')
            ->with('success', 'Stock Opname canceled.');
    }
}
