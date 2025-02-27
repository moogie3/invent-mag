<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POItem extends Model
{
    use HasFactory;
    protected $table = 'po_items';
    protected $fillable = [
        'po_id',
        'product_id',
        'quantity',
        'price',
        'total'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder() {
        return $this->belongsTo(Purchase::class , 'po_id');
    }
}