<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class POItem extends Model
{
    protected $table = 'po_items';
    protected $fillable = [
        'po_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'total'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder() {
        return $this->belongsTo(Purchase::class);
    }
}