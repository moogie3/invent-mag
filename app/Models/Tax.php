<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Tax extends Model
{
    use HasFactory, BelongsToTenant;

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
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
