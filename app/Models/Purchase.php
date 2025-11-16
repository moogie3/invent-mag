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
 * @property string $invoice
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $payment_type
 * @property float $total
 * @property float $total_amount
 * @property float $sub_total
 * @property float $grand_total
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Purchase extends Model
{
    use HasFactory;
    protected $table = 'po';
    protected $fillable = [
        'invoice',
        'supplier_id',
        'user_id',
        'order_date',
        'due_date',
        'payment_type',
        'discount_total',
        'discount_total_type',
        'total',
        'status',
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

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->grand_total - $this->total_paid;
    }

    public function getGrandTotalAttribute()
    {
        // If the items relationship is loaded, calculate precisely.
        if ($this->relationLoaded('items')) {
            $subTotal = $this->items->sum('total');
            $orderDiscount = \App\Helpers\PurchaseHelper::calculateDiscount(
                $subTotal,
                $this->discount_total,
                $this->discount_total_type
            );
            return $subTotal - $orderDiscount;
        }
        
        // Otherwise, fall back to the stored total, which is sufficient for most cases.
        return $this->attributes['total'];
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
    ];

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return \Database\Factories\PurchaseFactory::new();
    }

    /**
     * Get the path to view the model.
     *
     * @return string
     */
    public function path(): string
    {
        return route('admin.po.view', $this);
    }
}