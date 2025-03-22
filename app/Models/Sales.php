<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sales extends Model
{
    use HasFactory;
    protected $table = 'sales';
    protected $fillable = ['invoice', 'customer_id', 'order_date', 'due_date', 'payment_type', 'total', 'status', 'payment_date'];

    protected $attributes = [
        'status' => 'Unpaid',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function items()
    {
        return $this->hasMany(SalesItem::class, 'sales_id');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class);
    }

    public function getTotalAfterDiscountAttribute()
    {
        $total = $this->total;
        if ($this->discount) {
            return $this->discount->applyDiscount($total);
        }
        return $total;
    }

    protected $casts = [
        'total' => 'float',
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
    ];
}