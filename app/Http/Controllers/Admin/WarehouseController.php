<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->warehouseService->getWarehouseIndexData($entries);

        return view('admin.warehouse.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $result = $this->warehouseService->createWarehouse($request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse created successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse created');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
            }
            return redirect()->route('admin.warehouse')->with('error', 'Warehouse not found.');
        }

        $data = $request->all();
        $data['is_main'] = $request->has('is_main') ? 1 : 0;

        $result = $this->warehouseService->updateWarehouse($warehouse, $data);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }
            return back()->with('error', $result['message'])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse updated successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse updated');
    }

    public function destroy(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
            }
            return redirect()->route('admin.warehouse')->with('error', 'Warehouse not found.');
        }

        $result = $this->warehouseService->deleteWarehouse($warehouse);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }
            return redirect()->route('admin.warehouse')->with('error', $result['message']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse deleted successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse deleted');
    }

    public function setMain(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
            }
            return redirect()->route('admin.warehouse')->with('error', 'Warehouse not found.');
        }

        $result = $this->warehouseService->setMainWarehouse($warehouse);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }
            return redirect()->route('admin.warehouse')->with('error', $result['message']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Main warehouse updated successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Main warehouse updated successfully');
    }

    public function unsetMain(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
            }
            return redirect()->route('admin.warehouse')->with('error', 'Warehouse not found.');
        }
        
        $result = $this->warehouseService->unsetMainWarehouse($warehouse);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }
            return redirect()->route('admin.warehouse')->with('error', $result['message']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Main warehouse status removed.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Main warehouse status removed');
    }

    public function view(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
            }
            return redirect()->route('admin.warehouse')->with('error', 'Warehouse not found.');
        }

        // Get all PO items for this warehouse with remaining quantity
        $poItems = \App\Models\POItem::with(['product.category', 'product.unit', 'purchaseOrder'])
            ->whereHas('purchaseOrder', function($q) use ($id) {
                $q->where('warehouse_id', $id);
            })
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($item) {
                $product = $item->product;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'quantity' => $item->remaining_quantity,
                    'price' => $item->price,
                    'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : null,
                    'po_id' => $item->po_id,
                    'po_date' => $item->purchaseOrder ? $item->purchaseOrder->order_date?->format('Y-m-d') : null,
                    'category' => $product->category->name ?? 'N/A',
                    'unit' => $product->unit->name ?? 'N/A'
                ];
            });

        $totalStock = $poItems->sum('quantity');
        $totalProducts = $poItems->pluck('id')->unique()->count();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'address' => $warehouse->address,
                    'description' => $warehouse->description,
                    'is_main' => $warehouse->is_main,
                    'po_items' => $poItems->toArray(),
                    'total_products' => $totalProducts,
                    'total_stock' => $totalStock
                ]
            ]);
        }

        return view('admin.warehouse.view', compact('warehouse'));
    }

    /**
     * @group Warehouses
     * @summary Export All Warehouses
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
     * @response 200 "The exported file."
     */
    public function exportAll(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $file = $this->warehouseService->exportAllWarehouses($request->export_option);
            return $file;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting warehouses. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
