<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class SalesItem extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'sales_items';
    protected $fillable = [
        'sales_id',
        'product_id',
        'quantity',
        'discount',
        'discount_type',
        'customer_price',
        'total',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $with = ['product'];
    protected $appends = ['price'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
    public function sales(){
        return $this->belongsTo(Sales::class , 'sales_id');
    }

    public function getPriceAttribute() {
        return $this->customer_price;
    }

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return \Database\Factories\SalesItemFactory::new();
    }
}