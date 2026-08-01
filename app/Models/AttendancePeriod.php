<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'period_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
