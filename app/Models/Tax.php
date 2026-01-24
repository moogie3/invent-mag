<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Tax extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['name', 'rate', 'is_active'];

    protected $guarded = [
        'id',
        'tenant_id',
    ];
}
