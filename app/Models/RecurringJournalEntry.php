<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;

class RecurringJournalEntry extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'last_generated_at',
        'next_generation_date',
        'transactions',
        'template_account_code',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'transactions' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_generated_at' => 'datetime',
        'next_generation_date' => 'date',
        'is_active' => 'boolean',
    ];

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTemplateAccountAttribute(): ?Account
    {
        if ($this->template_account_code) {
            return Account::where('code', $this->template_account_code)
                ->where('tenant_id', $this->tenant_id)
                ->first();
        }
        return null;
    }

    public function isDueForGeneration(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && Carbon::now()->isAfter($this->end_date)) {
            return false;
        }

        return Carbon::now()->isAfter($this->next_generation_date) ||
               Carbon::now()->isSameDay($this->next_generation_date);
    }

    public function calculateNextGenerationDate(): Carbon
    {
        $current = Carbon::parse($this->next_generation_date);

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return $current->addDays($this->interval);
            case self::FREQUENCY_WEEKLY:
                return $current->addWeeks($this->interval);
            case self::FREQUENCY_MONTHLY:
                return $current->addMonths($this->interval);
            case self::FREQUENCY_QUARTERLY:
                return $current->addMonths($this->interval * 3);
            case self::FREQUENCY_YEARLY:
                return $current->addYears($this->interval);
            default:
                return $current->addMonths(1);
        }
    }

    public function generateJournalEntry(Account $templateAccount = null): ?JournalEntry
    {
        if (!$this->isDueForGeneration()) {
            return null;
        }

        $accountingService = app(\App\Services\AccountingService::class);

        $description = $this->name;
        if ($templateAccount) {
            $description .= ' - ' . $templateAccount->name;
        }

        $transactions = [];
        foreach ($this->transactions as $tx) {
            $transactions[] = [
                'account_code' => $tx['account_code'],
                'type' => $tx['type'],
                'amount' => $tx['amount'],
            ];
        }

        $journalEntry = $accountingService->createJournalEntry(
            $description,
            Carbon::now(),
            $transactions,
            $this
        );

        $this->update([
            'last_generated_at' => now(),
            'next_generation_date' => $this->calculateNextGenerationDate(),
        ]);

        return $journalEntry;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForGeneration($query)
    {
        return $query->active()
            ->whereDate('next_generation_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', now());
            });
    }
}
