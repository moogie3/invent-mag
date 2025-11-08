<?php

namespace Database\Factories;

use App\Models\SalesPipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_pipeline_id' => SalesPipeline::factory(),
            'name' => $this->faker->unique()->word,
            'position' => $this->faker->randomDigit(),
            'is_closed' => false,
        ];
    }
}
