<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class POItem extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'po_items';
    protected $fillable = [
        'tenant_id',
        'po_id',
        'product_id',
        'quantity',
        'price',
        'discount',
        'discount_type',
        'total',
        'expiry_date',
        'remaining_quantity'
    ];

    protected $casts = [
        'quantity' => 'float',
        'total' => 'float',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder() {
        return $this->belongsTo(Purchase::class , 'po_id');
    }

    public static function getExpiringSoonItems()
    {
        $thirtyDaysFromNow = now()->addDays(30);
        return self::with('product')->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->get();
    }

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return \Database\Factories\POItemFactory::new();
    }
}
