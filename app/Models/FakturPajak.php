<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FakturPajak extends Model
{
    // BelongsToTenant: auto-sets tenant_id on creating, auto-scopes all queries
    use BelongsToTenant;

    protected $table = 'faktur_pajak';

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'tanggal_faktur' => 'date',
            'djp_submitted_at' => 'datetime',
            'djp_response_at' => 'datetime',
            'djp_response_payload' => 'array',
            'is_pengganti' => 'boolean',
            'dpp' => 'decimal:2',
            'ppn' => 'decimal:2',
            'ppnbm' => 'decimal:2',
            'ppn_rate' => 'decimal:2',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /**
     * The Sales record this Faktur Pajak was generated from.
     * NOTE: "Sales" is the invoice model — there is no separate Invoice model.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FakturPajakItem::class);
    }

    public function originalFaktur(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class, 'original_faktur_id');
    }

    public function nsfpPool(): BelongsTo
    {
        return $this->belongsTo(NsfpPool::class, 'nsfp', 'nsfp');
    }

    // -----------------------------------------------------------------------
    // Business Logic Helpers
    // -----------------------------------------------------------------------

    public function isSubmittable(): bool
    {
        return $this->status === 'draft';
    }

    public function totalTagihan(): float
    {
        return (float) $this->dpp + (float) $this->ppn + (float) $this->ppnbm;
    }

    public function formattedNsfp(): string
    {
        // Already stored in formatted form: 010.000-26.00000001
        return $this->nsfp;
    }

    /**
     * Human-readable status label.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'amended' => 'Amended',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Bootstrap color for status badge.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'submitted' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            'amended' => 'warning',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }
}
