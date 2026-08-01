<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToTenant, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'shopname',
        'address',
        'email',
        'avatar',
        'timezone',
        'system_settings',
        'tutorial_completed',
        // NOTE: accounting_settings intentionally removed — now stored on tenants table.
        // The column still exists on users for backward-compat reads during migration window.
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
        ];
    }

    /**
     * Get the user's avatar URL
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? asset("storage/{$value}")
                : asset('img/default-avatar.png'),
        );
    }
}
