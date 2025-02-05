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
        'product_id',
        'quantity',
        'price',
        'status'
    ];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected $casts = [
        'price' => 'float',
        'quantity' => 'float'
    ];
}
