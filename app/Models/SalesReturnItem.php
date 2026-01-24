<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class SalesReturnItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'sales_return_id',
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    /**
     * Get the sales return that the item belongs to.
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * Get the product associated with the sales return item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'total' => 'float',
    ];
}
