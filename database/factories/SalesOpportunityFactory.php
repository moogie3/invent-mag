<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PipelineStage;
use App\Models\SalesPipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesOpportunity>
 */
class SalesOpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'sales_pipeline_id' => SalesPipeline::factory(),
            'pipeline_stage_id' => PipelineStage::factory(),
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => 'open',
            'amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
