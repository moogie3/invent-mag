<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_EXCLUSIVE = 'exclusive';

    public const TYPE_INCLUSIVE = 'inclusive';

    public const TYPE_NONE = 'none';

    public const TYPES = [self::TYPE_EXCLUSIVE, self::TYPE_INCLUSIVE, self::TYPE_NONE];

    protected $fillable = [
        'name',
        'rate',
        'type',
        'description',
        'is_active',
        'is_default',
        'is_ppn',
        'is_pph',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_ppn' => 'boolean',
            'is_pph' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->forJurisdiction();
    }

    public function scopeDefault($query)
    {
        // is_active is part of it: an inactive tax cannot be selected in any
        // dropdown, so a default that is switched off would be shown as "the
        // default" while every document books with the fallback instead.
        return $query->where('is_default', true)->where('is_active', true)->forJurisdiction();
    }

    /**
     * The tax a document books with when the user picked none: the flagged
     * default, else the first active tax this jurisdiction may use.
     *
     * This is the single fallback for every writer. It used to be spelled out
     * at sixteen call sites, two of them falling back to a collection ordered
     * BY NAME while the rest took whatever the database returned first. With
     * no ORDER BY there is no defined "first", so the same tenant could book
     * sales, purchases, quotations and POS orders through four different
     * default taxes in the same session.
     */
    public static function effectiveDefault(): ?self
    {
        return static::default()->orderBy('id')->first()
            ?? static::active()->orderBy('id')->first();
    }

    /**
     * Restrict a query to the taxes the tenant's tax jurisdiction allows.
     *
     * PPN/PPh records only exist for the Indonesian jurisdiction, so tenants
     * on any other jurisdiction must not see them in dropdowns nor fall back
     * to them as a default tax. Tenants already gate the journal side on
     * isIndonesian(); this keeps selection and fallbacks consistent with it.
     * Unbound tenants (CLI, queue) are treated as non-Indonesian.
     */
    public function scopeForJurisdiction($query)
    {
        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;

        if ($tenant !== null && $tenant->isIndonesian()) {
            return $query;
        }

        return $query->where('is_ppn', false)->where('is_pph', false);
    }
}
