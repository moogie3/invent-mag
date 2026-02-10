<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Categories;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Helpers\CurrencyHelper;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function getPaginatedProducts(int $entries, array $filters = [])
    {
        $query = Product::with(['category', 'supplier', 'unit']);

        // Constrain the eager loaded warehouses if a filter is applied
        $query->with(['warehouses' => function ($q) use ($filters) {
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                $q->where('warehouses.id', $filters['warehouse_id']);
            }
        }]);

        if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
            $query->whereHas('warehouses', function ($q) use ($filters) {
                $q->where('warehouses.id', $filters['warehouse_id']);
            });
        }

        return $query->paginate($entries);
    }

    public function getProductFormData()
    {
        return [
            'categories' => Categories::all(),
            'units' => Unit::all(),
            'suppliers' => Supplier::all(),
            'warehouses' => Warehouse::all(),
            'mainWarehouse' => Warehouse::where('is_main', true)->first(),
            // 'lowStockProducts' => Product::getLowStockProducts(), // This static method on model is broken, logic moved to service
            'lowStockProducts' => Product::getLowStockProducts(),
        ];
    }

    public function createProduct(array $data)
    {
        $data['has_expiry'] = !empty($data['has_expiry']);

        // Remove warehouse_id from data as it's no longer on the products table
        // We will use it to initialize stock in the pivot table if provided
        $initialWarehouseId = $data['warehouse_id'] ?? null;
        unset($data['warehouse_id']);

        if (isset($data['image'])) {
            $image = $data['image'];
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('image', $imageName, 'public');
            $data['image'] = $imageName;
        }

        $product = Product::create($data);

        // Initialize stock in the pivot table
        if ($initialWarehouseId) {
            $warehouse = Warehouse::find($initialWarehouseId);
        } else {
            $warehouse = Warehouse::where('is_main', true)->first();
        }

        if ($warehouse) {
            ProductWarehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 0, // Initial stock is 0, add via Purchase/Adjustment
                'tenant_id' => $product->tenant_id,
            ]);
        }

        return $product;
    }

    public function quickCreateProduct(array $data)
    {
        $product = $this->createProduct($data);
        $product->load(['unit']);

        $product->unit_symbol = $product->unit->symbol;
        // Warehouse name is now ambiguous as product can be in multiple, return Main or 'Multiple'
        $mainWarehouse = $product->warehouses()->where('is_main', true)->first();
        $product->warehouse_name = $mainWarehouse ? $mainWarehouse->name : 'Multiple';
        $product->image_url = $product->image;

        return $product;
    }

    public function updateProduct(Product $product, array $data)
    {
        $data['has_expiry'] = !empty($data['has_expiry']);
        
        // warehouse_id is no longer updatable on the product itself
        unset($data['warehouse_id']);

        if (isset($data['image'])) {
            $oldImageName = $product->getRawOriginal('image');
            if (!empty($oldImageName)) {
                Storage::disk('public')->delete('image/' . $oldImageName);
            }

            $image = $data['image'];
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('image', $imageName, 'public');
            $data['image'] = $imageName;
        }

        $product->update($data);

        return $product;
    }

    public function deleteProduct(Product $product)
    {
        $imageName = $product->getRawOriginal('image');
        if (!empty($imageName)) {
            Storage::disk('public')->delete('image/' . $imageName);
        }

        $product->delete();
    }

    public function bulkDeleteProducts(array $ids)
    {
        $deletedCount = 0;
        $imagesDeleted = 0;

        DB::transaction(function () use ($ids, &$deletedCount, &$imagesDeleted) {
            $products = Product::whereIn('id', $ids)->get();

            if ($products->isEmpty()) {
                throw new \Exception('No products found with the provided IDs');
            }

            foreach ($products as $product) {
                $imageName = $product->getRawOriginal('image');
                if (!empty($imageName)) {
                    $imagePath = 'image/' . $imageName;

                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        $imagesDeleted++;
                    }
                }
            }

            $deletedCount = Product::whereIn('id', $ids)->delete();
        });

        return [
            'deleted_count' => $deletedCount,
            'images_deleted' => $imagesDeleted,
        ];
    }

    public function bulkExportProducts(array $filters, ?array $ids, string $exportOption)
    {
        $query = Product::with(['category', 'supplier', 'unit', 'warehouses']);
        
        if ($ids) {
            $query->whereIn('id', $ids);
        } else {
            if (isset($filters['category_id']) && $filters['category_id']) {
                $query->where('category_id', $filters['category_id']);
            }
            if (isset($filters['supplier_id']) && $filters['supplier_id']) {
                $query->where('supplier_id', $filters['supplier_id']);
            }
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                // Filter by existence in warehouse pivot
                $query->whereHas('warehouses', function($q) use ($filters) {
                    $q->where('warehouses.id', $filters['warehouse_id']);
                });
            }
            if (isset($filters['units_id']) && $filters['units_id']) {
                $query->where('units_id', $filters['units_id']);
            }
            if (isset($filters['search']) && $filters['search']) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('barcode', 'LIKE', "%{$search}%");
                });
            }
        }

        $products = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.product.bulk-export-pdf', compact('products'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('products.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=products.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($products) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Code',
                    'Name',
                    'Category',
                    'Supplier',
                    'Warehouse',
                    'Unit',
                    'Stock',
                    'Price',
                    'Selling Price',
                ]);

                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->code,
                        $product->name,
                        $product->category->name,
                        $product->supplier->name,
                        $product->warehouses->pluck('name')->implode(', '), // Multi warehouse support
                        $product->unit->name,
                        $product->total_stock, // Use accessor
                        CurrencyHelper::format($product->price),
                        CurrencyHelper::format($product->selling_price),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function bulkUpdateStock(array $updates, ?string $reason, ?int $adjustedBy, ?int $warehouseId = null)
    {
        // If warehouseId is provided, use it. Otherwise default to Main Warehouse.
        if ($warehouseId) {
            $targetWarehouse = Warehouse::find($warehouseId);
        } else {
            $targetWarehouse = Warehouse::where('is_main', true)->first();
        }

        if (!$targetWarehouse) {
             throw new \Exception('Target warehouse not found for bulk update.');
        }

        $updatedCount = 0;
        $stockChanges = [];

        DB::transaction(function () use ($updates, $reason, $adjustedBy, $targetWarehouse, &$updatedCount, &$stockChanges) {
            foreach ($updates as $update) {
                $product = Product::findOrFail($update['id']);
                
                // Get stock for the target warehouse
                $stockRecord = ProductWarehouse::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $targetWarehouse->id, 'tenant_id' => $product->tenant_id],
                    ['quantity' => 0]
                );

                $originalStock = $stockRecord->quantity;
                $newStock = $update['stock_quantity'];

                if ($originalStock != $newStock) {
                    $stockRecord->update([
                        'quantity' => $newStock,
                    ]);

                    $updatedCount++;

                    $adjustmentAmount = abs($newStock - $originalStock);
                    $adjustmentType = 'correction';

                    if ($newStock > $originalStock) {
                        $adjustmentType = 'increase';
                    } elseif ($newStock < $originalStock) {
                        $adjustmentType = 'decrease';
                    }

                    \App\Models\StockAdjustment::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $targetWarehouse->id, // Track warehouse
                        'adjustment_type' => $adjustmentType,
                        'quantity_before' => $originalStock,
                        'quantity_after' => $newStock,
                        'adjustment_amount' => $adjustmentAmount,
                        'reason' => $reason ?? "Bulk stock update ({$targetWarehouse->name})",
                        'adjusted_by' => $adjustedBy,
                        'tenant_id' => $product->tenant_id,
                    ]);

                    $stockChanges[] = [
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'original_stock' => $originalStock,
                        'new_warehouse_stock' => $newStock,
                        'new_stock_quantity' => $product->total_stock, // Return the NEW total stock
                        'change' => $newStock - $originalStock,
                        'low_stock_threshold' => $product->low_stock_threshold,
                    ];
                }
            }
        });

        return [
            'updated_count' => $updatedCount,
            'changes' => $stockChanges,
        ];
    }

    public function adjustProductStock(Product $product, float $adjustmentAmount, string $adjustmentType, ?string $reason, ?int $adjustedBy, int $warehouseId = null): Product
    {
        // If no warehouse specified, use Main
        if (!$warehouseId) {
            $warehouseId = Product::getMainWarehouseId();
        }
        
        if (!$warehouseId) {
             throw new \Exception('No warehouse specified and no main warehouse found.');
        }

        DB::transaction(function () use ($product, $adjustmentAmount, $adjustmentType, $reason, $adjustedBy, $warehouseId) {
            
            $stockRecord = ProductWarehouse::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'tenant_id' => $product->tenant_id],
                ['quantity' => 0]
            );

            $quantityBefore = $stockRecord->quantity;
            $quantityAfter = $quantityBefore;

            if ($adjustmentType === 'increase') {
                $stockRecord->increment('quantity', $adjustmentAmount);
                $quantityAfter = $quantityBefore + $adjustmentAmount;
            } elseif ($adjustmentType === 'decrease') {
                $newQuantity = max(0, $quantityBefore - $adjustmentAmount);
                $stockRecord->update(['quantity' => $newQuantity]);
                $quantityAfter = $newQuantity;
            } elseif ($adjustmentType === 'correction') {
                $stockRecord->update(['quantity' => $adjustmentAmount]);
                $quantityAfter = $adjustmentAmount;
            }

            // Record the adjustment
            \App\Models\StockAdjustment::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId, // Track warehouse
                'adjustment_type' => $adjustmentType,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'adjustment_amount' => $adjustmentAmount,
                'reason' => $reason,
                'adjusted_by' => $adjustedBy,
                'tenant_id' => $product->tenant_id,
            ]);
        });

        return $product;
    }

    public function searchProducts($query)
    {
        $productsQuery = Product::query();

        $productsQuery->with([
            'category' => function($q) {
                $q->select('id', 'name');
            },
            'unit' => function($q) {
                $q->select('id', 'name', 'symbol');
            },
            'supplier' => function($q) {
                $q->select('id', 'name');
            }
        ]);

        $productsQuery->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('code', 'LIKE', "%{$query}%")
              ->orWhere('barcode', 'LIKE', "%{$query}%");

            $q->orWhereHas('category', function ($cat) use ($query) {
                $cat->where('name', 'LIKE', "%{$query}%");
            });

            $q->orWhereHas('supplier', function ($sup) use ($query) {
                $sup->where('name', 'LIKE', "%{$query}%");
            });
        });

        return $productsQuery
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();
    }

    public function searchByBarcode(string $barcode)
    {
        return Product::with('unit')->where('barcode', $barcode)->first();
    }

    public function getExpiringSoonPOItems()
    {
        $thirtyDaysFromNow = now()->addDays(30);

        return \App\Models\POItem::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('remaining_quantity', '>', 0) // Only show items with remaining quantity
            ->with(['product' => function($query) {
                $query->select('id', 'name', 'code'); // Select only necessary product fields
            }])
            ->get();
    }

    public function getExpiringSoonPOItemsCount()
    {
        $thirtyDaysFromNow = now()->addDays(30);

        return \App\Models\POItem::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('remaining_quantity', '>', 0) // Only count items with remaining quantity
            ->count();
    }

    public function getRecentlyPurchasedExpiringPOItems()
    {
        $thirtyDaysFromNow = now()->addDays(30);
        $sevenDaysAgo = now()->subDays(7);

        return \App\Models\POItem::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('created_at', '>=', $sevenDaysAgo) // Filter for recently purchased items
            ->with(['product' => function($query) {
                $query->select('id', 'name', 'code'); // Select only necessary product fields
            }])
            ->get();
    }
}