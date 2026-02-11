<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class OpeningBalance extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'balance',
        'balance_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'balance_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeAttribute(): string
    {
        if ($this->balance > 0) {
            return 'debit';
        } elseif ($this->balance < 0) {
            return 'credit';
        }
        return 'neutral';
    }

    public function createJournalEntry(): JournalEntry
    {
        $accountingService = app(\App\Services\AccountingService::class);

        $transactions = [];

        if ($this->debit_amount > 0) {
            $transactions[] = [
                'account_code' => $this->account->code,
                'type' => 'debit',
                'amount' => $this->debit_amount,
            ];
        }

        if ($this->credit_amount > 0) {
            $transactions[] = [
                'account_code' => $this->account->code,
                'type' => 'credit',
                'amount' => $this->credit_amount,
            ];
        }

        if (!empty($transactions)) {
            return $accountingService->createJournalEntry(
                "Opening Balance - {$this->account->name}",
                $this->balance_date,
                $transactions,
                $this
            );
        }

        return null;
    }
}
