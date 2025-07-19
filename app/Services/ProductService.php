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
            'expiringSoonProducts' => Product::getExpiringSoonProducts(),
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

        if (!$data['has_expiry']) {
            $data['expiry_date'] = null;
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

    public function bulkUpdateStock(array $updates)
    {
        $updatedCount = 0;
        $stockChanges = [];

        DB::transaction(function () use ($updates, &$updatedCount, &$stockChanges) {
            foreach ($updates as $update) {
                $product = Product::findOrFail($update['id']);
                $originalStock = $product->stock_quantity;
                $newStock = $update['stock_quantity'];

                if ($originalStock != $newStock) {
                    $product->update([
                        'stock_quantity' => $newStock,
                    ]);

                    $updatedCount++;

                    $stockChanges[] = [
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'original_stock' => $originalStock,
                        'new_stock' => $newStock,
                        'change' => $newStock - $originalStock,
                    ];
                }
            }
        });

        return [
            'updated_count' => $updatedCount,
            'changes' => $stockChanges,
        ];
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
              ->orWhere('code', 'LIKE', "%{$query}%");

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
}