<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'price',
        'billing_cycle',
        'description',
        'max_users',
        'max_warehouses',
        'features',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'max_users' => 'integer',
            'max_warehouses' => 'integer',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Get all tenants subscribed to this plan.
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // -------------------------------------------------------------------------
    // Feature Checks
    // -------------------------------------------------------------------------

    /**
     * Check if this plan includes a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    /**
     * Check if this plan allows a given number of users.
     * Returns true if unlimited (-1) or count is within limit.
     */
    public function allowsUsers(int $count): bool
    {
        if ($this->max_users === -1) {
            return true;
        }

        return $count <= $this->max_users;
    }

    /**
     * Check if this plan allows a given number of warehouses.
     * Returns true if unlimited (-1) or count is within limit.
     */
    public function allowsWarehouses(int $count): bool
    {
        if ($this->max_warehouses === -1) {
            return true;
        }

        return $count <= $this->max_warehouses;
    }

    /**
     * Check if this plan offers a free trial.
     */
    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order plans by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // -------------------------------------------------------------------------
    // Static Helpers
    // -------------------------------------------------------------------------

    /**
     * Find a plan by its slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get the default plan (starter) for new tenants.
     */
    public static function getDefault(): ?self
    {
        return static::findBySlug(config('plans.default_plan', 'starter'));
    }
}
