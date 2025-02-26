<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sales extends Model
{
    use HasFactory;
    protected $table = 'sales';
    protected $fillable = [
        'invoice',
        'customer_id',
        'order_date',
        'payment_type',
        'total',
        'status',
        'payment_date'
    ];

    protected $attributes = [
    'status' => 'Unpaid',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function items(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class, 'sales_id');
    }

    protected $casts = [
        'total' => 'float',
        'order_date' => 'datetime',
        'payment_date' => 'datetime'
    ];
}