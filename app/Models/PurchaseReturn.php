<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Tax;

class PurchaseReturn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'purchase_id',
        'user_id',
        'return_date',
        'reason',
        'total_amount',
        'status',
        'tax_id',
        'tax_rate',
        'tax_amount',
        'tax_type',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    public static array $statuses = [
        'Pending',
        'Completed',
        'Canceled',   // single-L, consistent with SalesReturn::$statuses
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

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
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
        'tax_rate' => 'float',
        'tax_amount' => 'float',
    ];
}
