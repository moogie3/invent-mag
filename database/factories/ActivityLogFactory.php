<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => null,
            'user_name' => $this->faker->name(),
            'user_role' => $this->faker->randomElement(['superuser', 'admin', 'staff']),
            'module' => $this->faker->randomElement([
                'Product',
                'Sales',
                'Purchase Order',
                'Customer',
                'Supplier',
                'User',
                'Dashboard',
            ]),
            'action' => $this->faker->randomElement(['create', 'update', 'delete', 'login', 'logout', 'export']),
            'entity_type' => null,
            'entity_id' => null,
            'entity_label' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function withValues(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
