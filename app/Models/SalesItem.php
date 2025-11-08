<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;
    protected $table = 'sales_items';
    protected $fillable = [
        'sales_id',
        'product_id',
        'name',
        'quantity',
        'discount',
        'discount_type',
        'customer_price',
        'total',
    ];

    protected $with = ['product'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
    public function sale(){
        return $this->belongsTo(Sales::class , 'sales_id');
    }

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return \Database\Factories\SalesItemFactory::new();
    }
}