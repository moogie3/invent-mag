<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'code',
        'barcode',
        'name',
        'description',
        'image',
        'cover_url',
        'price',
        'selling_price',
        'category_id',
        'supplier_id',
        'units_id',
        'has_expiry',
        'low_stock_threshold',
        'parent_id',
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

    /**
     * Family anchor (variant #1 keeps the parent Shopee SKU as its code).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    /**
     * Sibling variants created by a Shopee split (excludes the anchor itself).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    /**
     * Total stock across every warehouse.
     *
     * Reads the eager-loaded relation when one exists so a product list stays
     * at one query for the whole page instead of one per row. Callers that
     * narrow `productWarehouses` down to a single warehouse must not read this
     * attribute — they only ever read main_warehouse_stock.
     */
    public function getTotalStockAttribute()
    {
        if ($this->relationLoaded('productWarehouses')) {
            return (float) $this->productWarehouses->sum('quantity');
        }

        return (float) $this->productWarehouses()->sum('quantity');
    }

    public function getStockQuantityAttribute()
    {
        return $this->total_stock;
    }

    private const MAIN_WAREHOUSE_ID = 'invent-mag.main-warehouse-id';

    /**
     * Resolved once per application instance — a request under FPM, a single
     * test under PHPUnit — rather than once per product. Serializing a product
     * list used to run this query for every row it held.
     *
     * The memo stores 0 for "no main warehouse" rather than null, because
     * Container::bound() is isset()-based: a bound null reads as unbound and
     * the query would run again on every call.
     */
    public static function getMainWarehouseId()
    {
        if (! app()->bound(self::MAIN_WAREHOUSE_ID)) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();

            app()->instance(self::MAIN_WAREHOUSE_ID, $mainWarehouse ? $mainWarehouse->id : 0);
        }

        return app(self::MAIN_WAREHOUSE_ID) ?: null;
    }

    /**
     * Drop the memo so a warehouse saved or deleted in this request is seen at once.
     */
    public static function forgetMainWarehouseId(): void
    {
        app()->forgetInstance(self::MAIN_WAREHOUSE_ID);
    }

    public function getMainWarehouseIdAttribute()
    {
        return self::getMainWarehouseId();
    }

    public function getMainWarehouseStockAttribute()
    {
        $mainWarehouseId = self::getMainWarehouseId();
        if (! $mainWarehouseId) {
            return 0;
        }

        $stockRecord = $this->stockRecordFor($mainWarehouseId);

        return $stockRecord ? max(0, (float) $stockRecord->quantity) : 0;
    }

    public function getWarehouseStock($warehouseId)
    {
        $stockRecord = $this->stockRecordFor($warehouseId);

        return $stockRecord ? max(0, (float) $stockRecord->quantity) : 0;
    }

    public function getMainWarehouse()
    {
        return Warehouse::where('is_main', true)->first();
    }

    /**
     * One product's stock row, reusing the eager-loaded relation when there is
     * one. Read paths only: deductStock() and addStock() still go to the
     * database, because the row they mutate has to be the current one.
     */
    private function stockRecordFor($warehouseId): ?ProductWarehouse
    {
        if ($this->relationLoaded('productWarehouses')) {
            return $this->productWarehouses->firstWhere('warehouse_id', $warehouseId);
        }

        return $this->productWarehouses()->where('warehouse_id', $warehouseId)->first();
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
        if (! $warehouseId) {
            return false;
        }

        $stock = $this->getWarehouseStock($warehouseId);

        return $stock >= $quantity;
    }

    public function deductStock($quantity, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? self::getMainWarehouseId();
        if (! $warehouseId) {
            return false;
        }

        $stockRecord = $this->getStockForWarehouse($warehouseId);

        if (! $stockRecord) {
            throw new \Exception('No stock record found for this product in the warehouse.');
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
        if (! $warehouseId) {
            return false;
        }

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
            // Local cache wins; remote-first Shopee cover shows while the
            // background download has not cached the file yet.
            get: fn ($value) => $value
                ? asset("storage/image/{$value}")
                : ($this->cover_url ?: asset('img/default_placeholder.png')),
        );
    }

    /**
     * Get the effective low stock threshold for this product
     */
    public function getLowStockThreshold(): int
    {
        // Use product-specific threshold if set, otherwise use default
        return $this->low_stock_threshold ?? self::DEFAULT_LOW_STOCK_THRESHOLD;
    }

    /**
     * Get all products with low stock (in main warehouse)
     *
     * @return Collection
     */
    public static function getLowStockProducts()
    {
        $tenantId = app('currentTenant')->id ?? null;
        $mainWarehouseId = self::getMainWarehouseId();

        if (! $mainWarehouseId) {
            return collect();
        }

        $items = ProductWarehouse::with(['product', 'warehouse'])
            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.warehouse_id', $mainWarehouseId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->select('product_warehouse.*')
            ->get();

        $items->each(function ($item) {
            if ($item->product) {
                $item->product->setAppends([]);
            }
        });

        return $items;
    }

    /**
     * Get all products with low stock per warehouse (for modal display)
     *
     * @return Collection
     */
    public static function getLowStockProductsPerWarehouse()
    {
        $tenantId = app('currentTenant')->id ?? null;

        $items = ProductWarehouse::with(['product', 'warehouse'])
            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->select('product_warehouse.*')
            ->get();

        $items->each(function ($item) {
            if ($item->product) {
                $item->product->setAppends([]);
            }
        });

        return $items;
    }

    /**
     * Count how many products have low stock (in main warehouse)
     */
    public static function lowStockCount(): int
    {
        $tenantId = app('currentTenant')->id ?? null;
        $mainWarehouseId = self::getMainWarehouseId();

        if (! $mainWarehouseId) {
            return 0;
        }

        return ProductWarehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.warehouse_id', $mainWarehouseId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->count();
    }

    /**
     * Count how many products have low stock for a specific warehouse
     */
    public static function lowStockCountForWarehouse(?int $warehouseId): int
    {
        $tenantId = app('currentTenant')->id ?? null;

        if (! $warehouseId) {
            return 0;
        }

        return ProductWarehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.warehouse_id', $warehouseId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->count();
    }

    /**
     * Count how many distinct products have low stock across ALL warehouses
     * Used for header stock status - shows total products with low stock in any warehouse
     */
    public static function lowStockCountAllWarehouses(): int
    {
        $tenantId = app('currentTenant')->id ?? null;

        return ProductWarehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->distinct('product_warehouse.product_id')
            ->count('product_warehouse.product_id');
    }

    /**
     * Get low stock count per warehouse
     */
    public static function lowStockCountPerWarehouse(): array
    {
        $tenantId = app('currentTenant')->id ?? null;

        return ProductWarehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->join('warehouses', 'product_warehouse.warehouse_id', '=', 'warehouses.id')
            ->where('product_warehouse.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('product_warehouse.quantity', '>=', 0)
            ->whereRaw('COALESCE(products.low_stock_threshold, ?) >= product_warehouse.quantity', [self::DEFAULT_LOW_STOCK_THRESHOLD])
            ->select('warehouses.id', 'warehouses.name', DB::raw('count(*) as low_stock_count'))
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->name => $item->low_stock_count];
            })
            ->toArray();
    }

    /**
     * Get products that will expire soon (within 30 days)
     *
     * @return Collection
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
