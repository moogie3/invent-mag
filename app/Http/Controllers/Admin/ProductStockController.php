<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ProductStockController extends Controller
{
    public function mainStock($id)
    {
        $product = Product::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'main_warehouse_id' => $product->main_warehouse_id,
            'main_warehouse' => $product->getMainWarehouse() ? [
                'id' => $product->getMainWarehouse()->id,
                'name' => $product->getMainWarehouse()->name,
            ] : null,
            'main_warehouse_stock' => $product->main_warehouse_stock,
            'total_stock' => $product->total_stock,
            'is_low_stock' => $product->isLowStock(),
            'is_out_of_stock' => $product->isOutOfStock(),
            'low_stock_threshold' => $product->getLowStockThreshold(),
        ]);
    }

    public function warehouseStock($id, $warehouseId)
    {
        $product = Product::findOrFail($id);
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $stock = $product->getWarehouseStock($warehouseId);
        
        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'warehouse_stock' => $stock,
            'is_low_stock' => $stock <= $product->getLowStockThreshold(),
            'is_out_of_stock' => $stock <= 0,
            'low_stock_threshold' => $product->getLowStockThreshold(),
        ]);
    }

    public function checkStock(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $products = $request->input('products');
        $warehouseId = $request->input('warehouse_id') ?? Product::getMainWarehouseId();

        if (!$warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'No main warehouse configured.',
                'available' => false,
            ], 400);
        }

        $warehouse = Warehouse::find($warehouseId);
        $results = [];
        $allAvailable = true;

        foreach ($products as $item) {
            $product = Product::find($item['id']);
            $requestedQty = (int) $item['quantity'];
            $availableQty = $product ? $product->getWarehouseStock($warehouseId) : 0;
            
            $sufficient = $availableQty >= $requestedQty;
            
            if (!$sufficient) {
                $allAvailable = false;
            }

            $results[] = [
                'id' => $product ? $product->id : null,
                'product_name' => $product ? $product->name : 'Unknown',
                'requested' => $requestedQty,
                'available' => $availableQty,
                'shortage' => max(0, $requestedQty - $availableQty),
                'sufficient' => $sufficient,
            ];
        }

        return response()->json([
            'success' => true,
            'available' => $allAvailable,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouse ? $warehouse->name : 'Unknown',
            'items' => $results,
        ]);
    }

    public function bulkMainStock(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);

        $productIds = $request->input('product_ids');
        $products = Product::whereIn('id', $productIds)->get();
        
        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'main_warehouse_id' => $product->main_warehouse_id,
                'main_warehouse_stock' => $product->main_warehouse_stock,
                'total_stock' => $product->total_stock,
                'is_low_stock' => $product->isLowStock(),
                'is_out_of_stock' => $product->isOutOfStock(),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $results,
        ]);
    }

    public function allWarehouseStock($id)
    {
        $product = Product::findOrFail($id);
        
        $warehouses = $product->warehouses()->get()->map(function ($warehouse) use ($product) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'is_main' => $warehouse->is_main,
                'stock' => $warehouse->pivot ? $warehouse->pivot->quantity : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'main_warehouse_stock' => $product->main_warehouse_stock,
            'total_stock' => $product->total_stock,
            'low_stock_threshold' => $product->getLowStockThreshold(),
            'warehouses' => $warehouses,
        ]);
    }
}
