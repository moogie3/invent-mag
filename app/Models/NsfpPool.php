<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NsfpPool extends Model
{
    use BelongsToTenant;

    protected $table = 'nsfp_pool';

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function fakturPajak(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class);
    }

    /**
     * Scope: only available (unassigned) NSFP entries, ordered FIFO.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->orderBy('nsfp');
    }
}
