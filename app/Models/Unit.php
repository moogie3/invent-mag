<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Unit extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'units';
    protected $fillable = [
        'name',
        'symbol',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];
}