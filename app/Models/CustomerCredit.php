<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCredit extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'customer_credits';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sales_return_id',
        'amount',
        'used_amount',
        'reason',
        'status',
        'expires_at',
    ];

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'amount' => 'float',
        'used_amount' => 'float',
        'expires_at' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function getAvailableAmountAttribute(): float
    {
        return $this->amount - $this->used_amount;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && !$this->is_expired && $this->available_amount > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->whereRaw('amount > used_amount');
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
