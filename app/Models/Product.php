<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

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
        'stock_quantity',
        'category_id',
        'supplier_id',
        'units_id',
        'has_expiry',
        'low_stock_threshold',
        'warehouse_id', // Add this field to fillable
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'stock_quantity' => 'float',
        'has_expiry' => 'boolean',
        'low_stock_threshold' => 'integer',
        'warehouse_id' => 'integer', // Fixed the missing quote
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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
     * Get all products with low stock
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLowStockProducts()
    {
        // This query uses a subquery to compare each product's stock_quantity
        // against its own threshold or the default threshold
        return self::whereRaw('stock_quantity <= COALESCE(low_stock_threshold, ?)', [self::DEFAULT_LOW_STOCK_THRESHOLD])->get();
    }

    /**
     * Count how many products have low stock
     *
     * @return int
     */
    public static function lowStockCount(): int
    {
        return self::whereRaw('stock_quantity <= COALESCE(low_stock_threshold, ?)', [self::DEFAULT_LOW_STOCK_THRESHOLD])->count();
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
