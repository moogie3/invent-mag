<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'suppliers';
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone_number',
        'location',
        'payment_terms',
        'email',
        'image'
    ];

    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if ($value && $value !== 'default_placeholder.png') {
                    return asset("storage/image/{$value}");
                }
                return asset('img/default_placeholder.png');
            }
        );
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function interactions()
    {
        return $this->hasMany(SupplierInteraction::class);
    }
}
