<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'description',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
}