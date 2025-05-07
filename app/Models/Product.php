<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = [
        'code',
        'name',
        'description',
        'image',
        'price',
        'selling_price',
        'stock_quantity',
        'category_id',
        'supplier_id',
        'units_id',
        'has_expiry',
        'expiry_date'
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'stock_quantity' => 'float',
        'has_expiry' => 'boolean',
        'expiry_date' => 'date'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'units_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
            ? asset("storage/image/{$value}") // Convert filename to full URL
            : asset("storage/default.jpg"), // Default image if no image exists
        );
    }
}