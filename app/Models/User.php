<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\BelongsToTenant;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, BelongsToTenant;
    protected $table = 'users';
    protected $fillable = [
        'name',
        'shopname',
        'address',
        'email',
        'avatar',
        'timezone',
    ];

    protected $guarded = [
        'id',
        'password',
        'tenant_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'system_settings' => 'array',
            'accounting_settings' => 'array',
        ];
    }

    /**
     * Get the user's avatar URL
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function avatar(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn($value) => $value
                ? asset("storage/{$value}")
                : asset('img/default-avatar.png'),
        );
    }
}