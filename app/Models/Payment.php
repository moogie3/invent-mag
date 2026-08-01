<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'paymentable_id',
        'paymentable_type',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function paymentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the path to view the model.
     */
    public function path(): string
    {
        if ($this->paymentable) {
            return $this->paymentable->path();
        }

        return '#'; // Fallback if no paymentable is found
    }
}
