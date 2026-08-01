<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'account_id',
        'period_start',
        'period_end',
        'opening_balance',
        'closing_balance',
        'total_debit',
        'total_credit',
        'matched_amount',
        'unmatched_amount',
        'statement_lines_count',
        'status',
        'created_by',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'float',
        'closing_balance' => 'float',
        'total_debit' => 'float',
        'total_credit' => 'float',
        'matched_amount' => 'float',
        'unmatched_amount' => 'float',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Closing balance expected from the statement lines.
     */
    public function getComputedClosingBalanceAttribute(): float
    {
        return $this->opening_balance + $this->total_credit - $this->total_debit;
    }

    /**
     * Absolute difference between statement closing and computed closing.
     */
    public function getVarianceAttribute(): float
    {
        return round($this->closing_balance - $this->computed_closing_balance, 2);
    }
}
