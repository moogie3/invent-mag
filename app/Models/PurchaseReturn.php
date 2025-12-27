<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Concerns\BelongsToTenant;

class PurchaseReturn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'user_id',
        'return_date',
        'reason',
        'total_amount',
        'status',
    ];

    public static array $statuses = [
        'Pending',
        'Completed',
        'Cancelled',
    ];


    /**
     * Get the purchase that the return belongs to.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Get the user who created the return.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the items for the purchase return.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Get all of the payments for the purchase return.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * Get all of the journal entries for the purchase return.
     */
    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'transactionable');
    }

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'float',
    ];
}
