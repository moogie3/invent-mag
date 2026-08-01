<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'bank_reconciliation_id',
        'line_date',
        'description',
        'reference',
        'type',
        'amount',
        'status',
        'journal_entry_id',
        'matched_at',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'line_date' => 'date',
        'amount' => 'float',
        'matched_at' => 'datetime',
    ];

    public const STATUS_UNMATCHED = 'unmatched';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_MANUALLY_MATCHED = 'manually_matched';

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function isMatched(): bool
    {
        return in_array($this->status, [self::STATUS_MATCHED, self::STATUS_MANUALLY_MATCHED], true);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', self::STATUS_UNMATCHED);
    }

    public function scopeMatched($query)
    {
        return $query->whereIn('status', [self::STATUS_MATCHED, self::STATUS_MANUALLY_MATCHED]);
    }
}
