<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Quotation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'quotation_date' => 'datetime',
        'valid_until' => 'datetime',
        'items' => 'array',
        'tax_amount' => 'float',
        'tax_rate' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'discount_value' => 'float',
        'total' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function convertedToSales()
    {
        return $this->belongsTo(Sales::class, 'converted_to_sales_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
