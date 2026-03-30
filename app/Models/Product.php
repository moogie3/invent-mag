<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;
use App\Models\ProductWarehouse;

class Product extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'products';
    protected $fillable = [
        'code',
        'barcode',
        'name',
        'description',
        'image',
        'price',
        'selling_price',
        'category_id',
        'supplier_id',
        'units_id',
        'has_expiry',
        'low_stock_threshold',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'has_expiry' => 'boolean',
        'low_stock_threshold' => 'integer',
    ];

    protected $appends = ['stock_quantity', 'total_stock', 'main_warehouse_stock', 'main_warehouse_id'];

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function productWarehouses()
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    public function getTotalStockAttribute()
    {
        return (float) $this->productWarehouses()->sum('quantity');
    }

    public function getStockQuantityAttribute()
    {
        return $this->total_stock;
    }

    public static function getMainWarehouseId()
    {
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        return $mainWarehouse ? $mainWarehouse->id : null;
    }

    public function getMainWarehouseIdAttribute()
    {
        return self::getMainWarehouseId();
    }

    public function getMainWarehouseStockAttribute()
    {
        $mainWarehouseId = self::getMainWarehouseId();
        if (!$mainWarehouseId) return 0;
        
        $stockRecord = $this->productWarehouses()
            ->where('warehouse_id', $mainWarehouseId)
            ->first();
        
        return $stockRecord ? max(0, (float) $stockRecord->quantity) : 0;
    }

    public function getWarehouseStock($warehouseId)
    {
        $stockRecord = $this->productWarehouses()
            ->where('warehouse_id', $warehouseId)
            ->first();
        
        return $stockRecord ? max(0, (float) $stockRecord->quantity) : 0;
    }

    public function getMainWarehouse()
    {
        return Warehouse::where('is_main', true)->first();
    }

    public function getStockForWarehouse($warehouseId)
    {
        return $this->productWarehouses()
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    public function hasSufficientStock($quantity, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? self::getMainWarehouseId();
        if (!$warehouseId) return false;
        
        $stock = $this->getWarehouseStock($warehouseId);
        return $stock >= $quantity;
    }

    public function deductStock($quantity, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? self::getMainWarehouseId();
        if (!$warehouseId) return false;
        
        $stockRecord = $this->getStockForWarehouse($warehouseId);
        
        if (!$stockRecord) {
            throw new \Exception("No stock record found for this product in the warehouse.");
        }
        
        $currentStock = (float) $stockRecord->quantity;
        
        if ($currentStock < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$currentStock}, Requested: {$quantity}");
        }
        
        $stockRecord->decrement('quantity', $quantity);
        
        return true;
    }

    public function addStock($quantity, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? self::getMainWarehouseId();
        if (!$warehouseId) return false;
        
        $stockRecord = $this->getStockForWarehouse($warehouseId);
        
        if ($stockRecord) {
            $stockRecord->increment('quantity', $quantity);
        } else {
            ProductWarehouse::create([
                'product_id' => $this->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'tenant_id' => $this->tenant_id,
            ]);
        }
        
        return true;
    }

    public function isLowStock($warehouseId = null)
    {
        $stock = $warehouseId ? $this->getWarehouseStock($warehouseId) : $this->main_warehouse_stock;
        return $stock <= $this->getLowStockThreshold();
    }

    public function isOutOfStock($warehouseId = null)
    {
        $stock = $warehouseId ? $this->getWarehouseStock($warehouseId) : $this->main_warehouse_stock;
        return $stock <= 0;
    }

    // Define global default low stock threshold
    const DEFAULT_LOW_STOCK_THRESHOLD = 10;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'units_id');
    }

    public function poItems()
    {
        return $this->hasMany(POItem::class);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
                ? asset("storage/image/{$value}") // Convert filename to full URL
                : asset('img/default_placeholder.png'), // Default image if no image exists
        );
    }

    /**
     * Get the effective low stock threshold for this product
     *
     * @return int
     */
    public function getLowStockThreshold(): int
    {
        // Use product-specific threshold if set, otherwise use default
        return $this->low_stock_threshold ?? self::DEFAULT_LOW_STOCK_THRESHOLD;
    }



    /**
     * Get all products with low stock (per warehouse)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLowStockProducts()
    {
        $tenantId = app('currentTenant')->id ?? null;
        
        return \App\Models\ProductWarehouse::with(['product', 'warehouse'])
            ->where('product_warehouse.tenant_id', $tenantId)
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereRaw('products.low_stock_threshold >= product_warehouse.quantity');
            })
            ->get();
    }

    /**
     * Count how many products have low stock (per warehouse)
     *
     * @return int
     */
    public static function lowStockCount(): int
    {
        $tenantId = app('currentTenant')->id ?? null;
        
        return \App\Models\ProductWarehouse::where('product_warehouse.tenant_id', $tenantId)
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereRaw('products.low_stock_threshold >= product_warehouse.quantity');
            })
            ->count();
    }

    /**
     * Get products that will expire soon (within 30 days)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getExpiringSoonProducts()
    {
        $thirtyDaysFromNow = now()->addDays(30);

        return self::whereHas('poItems', function ($query) use ($thirtyDaysFromNow) {
            $query->whereNotNull('expiry_date')
                  ->where('expiry_date', '>', now())
                  ->where('expiry_date', '<=', $thirtyDaysFromNow);
        })->with(['poItems' => function ($query) use ($thirtyDaysFromNow) {
            $query->whereNotNull('expiry_date')
                  ->where('expiry_date', '>', now())
                  ->where('expiry_date', '<=', $thirtyDaysFromNow)
                  ->orderBy('expiry_date', 'asc');
        }])->get();
    }

    /**
     * Count products that will expire soon
     *
     * @return int
     */
    public static function expiringSoonCount()
    {
        $thirtyDaysFromNow = now()->addDays(30);

        return self::whereHas('poItems', function ($query) use ($thirtyDaysFromNow) {
            $query->whereNotNull('expiry_date')
                  ->where('expiry_date', '>', now())
                  ->where('expiry_date', '<=', $thirtyDaysFromNow);
        })->count();
    }

    public function getSoonestExpiryDateAttribute()
    {
        return $this->poItems->whereNotNull('expiry_date')->min('expiry_date');
    }
}
