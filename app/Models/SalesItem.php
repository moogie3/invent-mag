<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SalesItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use BelongsToTenant, HasFactory;

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

    protected $appends = ['price'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function getPriceAttribute()
    {
        return $this->customer_price;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['customer_price'] = $value;
    }

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return SalesItemFactory::new();
    }
}
