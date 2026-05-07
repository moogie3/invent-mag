<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'run_date' => 'date',
        'paid_date' => 'date',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}