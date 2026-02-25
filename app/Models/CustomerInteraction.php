<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'type',
        'notes',
        'interaction_date',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\CustomerInteractionFactory::new();
    }
}