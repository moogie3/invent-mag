<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * Display the stock transfer page.
     */
    public function index()
    {
        $warehouses = Warehouse::all();
        return view('admin.reports.stock-transfer', compact('warehouses'));
    }

    /**
     * Handle stock transfer between warehouses.
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
        ]);

        $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
        $product = Product::findOrFail($request->product_id);

        $fromStockRecord = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $fromWarehouse->id)
            ->first();

        $availableQty = $fromStockRecord ? $fromStockRecord->quantity : 0;

        if ($availableQty < $request->quantity) {
            return redirect()->back()
                ->with('error', "Insufficient stock. Available: {$availableQty}, Requested: {$request->quantity}");
        }

        DB::transaction(function () use ($request, $fromWarehouse, $toWarehouse, $product, $fromStockRecord, $availableQty) {
            $fromQty = $availableQty;
            $toStockRecord = ProductWarehouse::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $toWarehouse->id, 'tenant_id' => $product->tenant_id],
                ['quantity' => 0]
            );
            $toQty = $toStockRecord->quantity;

            $fromStockRecord->update(['quantity' => $fromQty - $request['quantity']]);
            
            $toStockRecord->update(['quantity' => $toQty + $request['quantity']]);

            StockAdjustment::create([
                'product_id' => $product->id,
                'warehouse_id' => $fromWarehouse->id,
                'adjustment_type' => 'transfer',
                'quantity_before' => $fromQty,
                'quantity_after' => $fromQty - $request['quantity'],
                'adjustment_amount' => $request['quantity'],
                'reason' => $request['reason'] ?? "Transfer to {$toWarehouse->name}",
                'adjusted_by' => auth()->id(),
                'tenant_id' => $product->tenant_id,
            ]);

            StockAdjustment::create([
                'product_id' => $product->id,
                'warehouse_id' => $toWarehouse->id,
                'adjustment_type' => 'transfer',
                'quantity_before' => $toQty,
                'quantity_after' => $toQty + $request['quantity'],
                'adjustment_amount' => $request['quantity'],
                'reason' => $request['reason'] ?? "Transfer from {$fromWarehouse->name}",
                'adjusted_by' => auth()->id(),
                'tenant_id' => $product->tenant_id,
            ]);
        });

        return redirect()->route('admin.reports.adjustment-log')
            ->with('success', "Successfully transferred {$request['quantity']} units of {$product->name} from {$fromWarehouse->name} to {$toWarehouse->name}");
    }

    /**
     * Get products by warehouse for transfer.
     */
    public function getProducts($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $products = $warehouse->productWarehouses()
            ->with('product:id,name,code')
            ->where('quantity', '>', 0)
            ->get();

        return response()->json($products->map(function ($pw) {
            return [
                'id' => $pw->product->id,
                'name' => $pw->product->name,
                'code' => $pw->product->code,
                'quantity' => $pw->quantity,
            ];
        }));
    }
}
