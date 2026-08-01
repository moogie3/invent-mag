<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'address',
        'description',
        'is_main',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    /**
     * Scope a query to only include the main warehouse.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Check if there is already a main warehouse other than this one
     *
     * @return bool
     */
    public static function hasMainWarehouse($exceptId = null)
    {
        $query = self::where('is_main', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function productWarehouses()
    {
        return $this->hasMany(ProductWarehouse::class);
    }
}
