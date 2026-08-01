<?php

namespace Database\Factories;

use App\Models\SalesPipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesPipeline>
 */
class SalesPipelineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
            'is_default' => false,
        ];
    }
}
