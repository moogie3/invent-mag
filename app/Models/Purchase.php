<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'total',
        'status',
        'payment_date',
    ];

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items() {
        return $this->hasMany(POItem::class, 'po_id');
    }

    protected $casts = [
        'total' => 'float',
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
    ];

}