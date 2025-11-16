<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'sourceable_id',
        'sourceable_type',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the parent sourceable model (e.g., Sale, Purchase).
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the individual debit/credit transactions for the journal entry.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}