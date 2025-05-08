<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $table = 'warehouses';
    protected $fillable = [
        'name',
        'address',
        'description',
        'is_main',
    ];

    /**
     * Scope a query to only include the main warehouse.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Check if there is already a main warehouse other than this one
     *
     * @return boolean
     */
    public static function hasMainWarehouse($exceptId = null)
    {
        $query = self::where('is_main', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
