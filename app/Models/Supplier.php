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
        'payment_terms'
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}