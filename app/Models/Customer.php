<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'address',
        'phone_number',
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

    public function sales()
    {
        return $this->hasMany(Sales::class);
    }

    public function interactions()
    {
        return $this->hasMany(CustomerInteraction::class);
    }
}