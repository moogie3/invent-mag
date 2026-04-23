<?php

namespace Database\Factories;

use App\Models\StockOpname;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'reference_number' => 'SO-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'status' => 'Draft',
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => null,
            'created_by' => \App\Models\User::factory(),
            'completed_at' => null,
            'total_variance_value' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (StockOpname $opname) {
            if (!$opname->tenant_id && $opname->warehouse) {
                $opname->update(['tenant_id' => $opname->warehouse->tenant_id]);
            }
        });
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Completed',
            'completed_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Canceled',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'In Progress',
        ]);
    }
}