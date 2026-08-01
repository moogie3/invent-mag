<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesReturn extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'sales_id',
        'user_id',
        'return_date',
        'reason',
        'notes',
        'terms_conditions',
        'total_amount',
        'tax_id',
        'tax_rate',
        'tax_amount',
        'tax_type',
        'status',
        'customer_credit_id',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    public static array $statuses = [
        'Pending',
        'Completed',
        'Canceled',
    ];

    /**
     * Get the sale that the return belongs to.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    /**
     * Get the user who created the return.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the items for the sales return.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    /**
     * Get all of the payments for the sales return.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * Get all of the journal entries for the sales return.
     */
    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'transactionable');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(CustomerCredit::class, 'customer_credit_id');
    }

    /**
     * Get the tax applied to this sales return (mirrors the parent sale's tax).
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
    ];
}
