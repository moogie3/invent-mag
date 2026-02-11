<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenant;

class Account extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'description',
        'level',
        'is_active',
        'is_contra',
        'normal_balance',
        'opening_balance',
        'opening_balance_date',
        'currency',
        'allow_manual_entry',
        'tax_type',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_contra' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public const NORMAL_BALANCE_DEBIT = 'debit';
    public const NORMAL_BALANCE_CREDIT = 'credit';

    /**
     * Get the parent account.
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the children accounts.
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get opening balances for this account.
     */
    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class);
    }

    /**
     * Get the current balance of this account.
     */
    public function getCurrentBalanceAttribute(): float
    {
        $debits = $this->transactions->where('type', 'debit')->sum('amount');
        $credits = $this->transactions->where('type', 'credit')->sum('amount');

        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }
        return $credits - $debits;
    }

    /**
     * Get balance at a specific date.
     */
    public function getBalanceAtDate($date): float
    {
        $debits = $this->transactions()
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('date', '<=', $date)
                    ->where('status', 'posted');
            })
            ->where('type', 'debit')
            ->sum('amount');

        $credits = $this->transactions()
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('date', '<=', $date)
                    ->where('status', 'posted');
            })
            ->where('type', 'credit')
            ->sum('amount');

        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }
        return $credits - $debits;
    }

    /**
     * Check if this account can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->transactions()->exists() &&
               !$this->children()->exists() &&
               $this->opening_balance == 0;
    }

    /**
     * Check if this is a contra account.
     */
    public function isContraAccount(): bool
    {
        return $this->is_contra;
    }

    /**
     * Get the effective normal balance.
     */
    public function getEffectiveNormalBalanceAttribute(): string
    {
        if ($this->is_contra) {
            return $this->normal_balance === self::NORMAL_BALANCE_DEBIT
                ? self::NORMAL_BALANCE_CREDIT
                : self::NORMAL_BALANCE_DEBIT;
        }
        return $this->normal_balance;
    }

    /**
     * Scope for active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for contra accounts.
     */
    public function scopeContra($query)
    {
        return $query->where('is_contra', true);
    }

    /**
     * Scope for accounts by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}