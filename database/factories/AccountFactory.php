<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
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
            'code' => $this->faker->unique()->numberBetween(6000, 9999),
            'type' => $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }
}