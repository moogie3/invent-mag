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
        // 'stock_quantity', // Removed for multi-warehouse support
        'category_id',
        'supplier_id',
        'units_id',
        'has_expiry',
        'low_stock_threshold',
        // 'warehouse_id', // Removed for multi-warehouse support
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        // 'stock_quantity' => 'float', // Removed
        'has_expiry' => 'boolean',
        'low_stock_threshold' => 'integer',
        // 'warehouse_id' => 'integer', // Removed
    ];

    protected $appends = ['stock_quantity', 'total_stock'];

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
        // Backward compatibility accessor
        return $this->total_stock;
    }

    // Add this method to get the main warehouse ID or null if none set
    public static function getMainWarehouseId()
    {
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        return $mainWarehouse ? $mainWarehouse->id : null;
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
        return \App\Models\ProductWarehouse::with(['product', 'warehouse'])
            ->whereHas('product')
            ->get()
            ->filter(function ($pw) {
                $threshold = $pw->product->low_stock_threshold ?? 10;
                return $pw->quantity <= $threshold;
            });
    }

    /**
     * Count how many products have low stock (per warehouse)
     *
     * @return int
     */
    public static function lowStockCount(): int
    {
        return \App\Models\ProductWarehouse::with(['product'])
            ->whereHas('product')
            ->get()
            ->filter(function ($pw) {
                $threshold = $pw->product->low_stock_threshold ?? 10;
                return $pw->quantity <= $threshold;
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
