<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payment_info' => 'json',
        'paid_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'order_number',
        'amount',
        'status',
        'snap_token',
        'paid_at',
        'payment_info',
        'change_type',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
