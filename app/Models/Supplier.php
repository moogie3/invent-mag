<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Supplier extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone_number',
        'location',
        'payment_terms',
        'email',
        'image',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value && $value !== 'default_placeholder.png' && Storage::disk('public')->exists('image/'.$value)) {
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
