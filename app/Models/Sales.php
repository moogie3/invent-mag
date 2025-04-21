<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sales extends Model
{
    use HasFactory;

    protected $table = 'sales';
    protected $fillable = [
        'invoice',
        'customer_id',
        'order_date',
        'due_date',
        'payment_type',
        'order_discount',
        'order_discount_type',
        'total',
        'status',
        'payment_date',
        'tax_rate',
        'total_tax' // ✅ Include new tax fields
    ];

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

    public function items(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'sales_id');
    }

    public function product(): BelongsTo
{
    return $this->belongsTo(Product::class, 'product_id');
}

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    protected $casts = [
        'total' => 'float',
        'total_tax' => 'float',
        'tax_rate' => 'float',
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
    ];
}
