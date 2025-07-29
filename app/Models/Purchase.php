<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $status
 * @property string $discount_total_type
 * @property float $discount_total
 */
class Purchase extends Model
{
    use HasFactory;
    protected $table = 'po';
    protected $fillable = [
        'invoice',
        'supplier_id',
        'order_date',
        'due_date',
        'payment_type',
        'discount_total',
        'discount_total_type',
        'total',
        'status',
        'payment_date',
    ];

    protected $appends = ['sub_total', 'grand_total'];

    protected $attributes = [
    'status' => 'Unpaid',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items() {
        return $this->hasMany(POItem::class, 'po_id');
    }

    public function getSubTotalAttribute()
    {
        return $this->items->sum('total');
    }

    public function getGrandTotalAttribute()
    {
        $orderDiscount = \App\Helpers\PurchaseHelper::calculateDiscount(
            $this->sub_total,
            $this->discount_total,
            $this->discount_total_type
        );
        return $this->sub_total - $orderDiscount;
    }

    public function getTotalAmountAttribute()
    {
        return $this->grand_total;
    }

    protected $casts = [
    'total' => 'float',
    'discount_total' => 'float',
    'order_date' => 'datetime',
    'due_date' => 'datetime',
    'payment_date' => 'datetime',
    ];

}