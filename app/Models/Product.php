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
        'name',
        'description',
        'image',
        'price',
        'selling_price',
        'quantity',
        'category_id',
        'supplier_id',
        'unit_id'
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'quantity' => 'float'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
            ? asset("storage/products/{$value}") // Convert filename to full URL
            : asset("storage/default.jpg"), // Default image if no image exists
        );
    }
}