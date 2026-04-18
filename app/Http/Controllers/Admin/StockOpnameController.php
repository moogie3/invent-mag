<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
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

        try {
            $opname = $this->stockOpnameService->createOpname(
                $request->warehouse_id,
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
        if ($stockOpname->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $stockOpname->load('warehouse', 'createdBy.roles');
        
        $items = $stockOpname->items()
            ->with('product')
            ->paginate(50);
            
        return view('admin.stock-opname.show', compact('stockOpname', 'items'));
    }

    public function updateCounts(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->tenant_id !== Auth::user()->tenant_id || $stockOpname->status === 'Completed') {
            abort(403);
        }

        $request->validate([
            'counts' => 'required|array',
            'counts.*' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->stockOpnameService->updateCounts($stockOpname, $request->counts);

            return redirect()->route('admin.stock-opname.show', $stockOpname->id)
                ->with('success', 'Counts updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(StockOpname $stockOpname)
    {
        if ($stockOpname->tenant_id !== Auth::user()->tenant_id || $stockOpname->status === 'Completed') {
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
        if ($stockOpname->tenant_id !== Auth::user()->tenant_id || $stockOpname->status === 'Completed') {
            abort(403);
        }

        $stockOpname->update(['status' => 'Canceled']);

        return redirect()->route('admin.stock-opname.index')
            ->with('success', 'Stock Opname canceled.');
    }
}