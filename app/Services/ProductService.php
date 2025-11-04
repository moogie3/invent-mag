<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Categories;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function getPaginatedProducts(int $entries)
    {
        return Product::with(['category', 'supplier', 'unit', 'warehouse'])->paginate($entries);
    }

    public function getProductFormData()
    {
        return [
            'categories' => Categories::all(),
            'units' => Unit::all(),
            'suppliers' => Supplier::all(),
            'warehouses' => Warehouse::all(),
            'mainWarehouse' => Warehouse::where('is_main', true)->first(),
            'lowStockProducts' => Product::getLowStockProducts(),
        ];
    }

    public function createProduct(array $data)
    {
        $data['has_expiry'] = !empty($data['has_expiry']);

        if (empty($data['warehouse_id'])) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if ($mainWarehouse) {
                $data['warehouse_id'] = $mainWarehouse->id;
            }
        }

        if (isset($data['image'])) {
            $image = $data['image'];
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);
            $data['image'] = $imageName;
        }

        return Product::create($data);
    }

    public function quickCreateProduct(array $data)
    {
        $product = $this->createProduct($data);
        $product->load(['unit', 'warehouse']);

        $product->unit_symbol = $product->unit->symbol;
        $product->warehouse_name = $product->warehouse ? $product->warehouse->name : 'None';
        $product->image_url = $product->image;

        return $product;
    }

    public function updateProduct(Product $product, array $data)
    {
        $data['has_expiry'] = !empty($data['has_expiry']);

        if (empty($data['warehouse_id'])) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if ($mainWarehouse) {
                $data['warehouse_id'] = $mainWarehouse->id;
            }
        }

        if (isset($data['image'])) {
            $oldImagePath = 'public/image/' . basename($product->image);

            if (!empty($product->image) && Storage::exists($oldImagePath)) {
                Storage::delete($oldImagePath);
            }

            $image = $data['image'];
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);
            $data['image'] = $imageName;
        }

        $product->update($data);

        return $product;
    }

    public function deleteProduct(Product $product)
    {
        if (!empty($product->image)) {
            Storage::delete('public/image/' . basename($product->image));
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
                if (!empty($product->image)) {
                    $imagePath = 'public/image/' . basename($product->image);

                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
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

    public function bulkUpdateStock(array $updates, ?string $reason, ?int $adjustedBy)
    {
        $updatedCount = 0;
        $stockChanges = [];

        DB::transaction(function () use ($updates, $reason, $adjustedBy, &$updatedCount, &$stockChanges) {
            foreach ($updates as $update) {
                $product = Product::findOrFail($update['id']);
                $originalStock = $product->stock_quantity;
                $newStock = $update['stock_quantity'];

                if ($originalStock != $newStock) {
                    $product->update([
                        'stock_quantity' => $newStock,
                    ]);

                    $updatedCount++;

                    $adjustmentAmount = abs($newStock - $originalStock);
                    $adjustmentType = 'correction'; // Default to correction

                    if ($newStock > $originalStock) {
                        $adjustmentType = 'increase';
                    } elseif ($newStock < $originalStock) {
                        $adjustmentType = 'decrease';
                    }

                    \App\Models\StockAdjustment::create([
                        'product_id' => $product->id,
                        'adjustment_type' => $adjustmentType,
                        'quantity_before' => $originalStock,
                        'quantity_after' => $newStock,
                        'adjustment_amount' => $adjustmentAmount,
                        'reason' => $reason ?? 'Bulk stock update',
                        'adjusted_by' => $adjustedBy,
                    ]);

                    $stockChanges[] = [
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'original_stock' => $originalStock,
                        'new_stock_quantity' => $newStock,
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

    public function adjustProductStock(Product $product, float $adjustmentAmount, string $adjustmentType, ?string $reason, ?int $adjustedBy): Product
    {
        DB::transaction(function () use ($product, $adjustmentAmount, $adjustmentType, $reason, $adjustedBy) {
            $quantityBefore = $product->stock_quantity;
            $quantityAfter = $quantityBefore;

            if ($adjustmentType === 'increase') {
                $product->increment('stock_quantity', $adjustmentAmount);
                $quantityAfter = $quantityBefore + $adjustmentAmount;
            } elseif ($adjustmentType === 'decrease') {
                // Ensure we don't go below zero
                $newQuantity = max(0, $quantityBefore - $adjustmentAmount);
                $product->update(['stock_quantity' => $newQuantity]);
                $quantityAfter = $newQuantity;
            } elseif ($adjustmentType === 'correction') {
                // For correction, adjustmentAmount is the target quantity
                $product->update(['stock_quantity' => $adjustmentAmount]);
                $quantityAfter = $adjustmentAmount;
            }

            // Record the adjustment
            \App\Models\StockAdjustment::create([
                'product_id' => $product->id,
                'adjustment_type' => $adjustmentType,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'adjustment_amount' => $adjustmentAmount,
                'reason' => $reason,
                'adjusted_by' => $adjustedBy,
            ]);
        });

        $product->refresh(); // Refresh the product model to get the latest stock_quantity
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