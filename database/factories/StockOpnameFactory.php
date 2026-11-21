<?php

namespace Database\Factories;

use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    /**
     * A sequence, not a random draw. stock_opnames carries
     * unique(['tenant_id', 'reference_number']), and mt_rand(1, 9999) repeated
     * across a 20-row batch lands on a duplicate about 1.8% of the time, which
     * failed test_index_displays_paginated_opnames once every ~25 runs with
     * SQLSTATE[23000] on the index. The suffix stays four digits because
     * StockOpnameService::generateReferenceNumber() reads it back with
     * substr(-4) and increments; no test mixes factory rows with a generated
     * one, so the two sequences never meet.
     */
    private static int $sequence = 0;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'reference_number' => sprintf('SO-%s-%04d', date('ymd'), ++self::$sequence),
            'warehouse_id' => Warehouse::factory(),
            'status' => 'Draft',
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => null,
            'created_by' => User::factory(),
            'completed_at' => null,
            'total_variance_value' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (StockOpname $opname) {
            if (! $opname->tenant_id && $opname->warehouse) {
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
