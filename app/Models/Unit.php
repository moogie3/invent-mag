<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use BelongsToTenant, HasFactory;

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
