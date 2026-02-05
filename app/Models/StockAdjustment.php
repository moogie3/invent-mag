<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class StockAdjustment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'product_id',
        'warehouse_id', // Added
        'adjustment_type',
        'quantity_before',
        'quantity_after',
        'adjustment_amount',
        'reason',
        'adjusted_by',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_before' => 'float',
        'quantity_after' => 'float',
        'adjustment_amount' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
