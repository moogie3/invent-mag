<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerInteraction>
 */
class CustomerInteractionFactory extends Factory
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
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['Call', 'Email', 'Meeting']),
            'notes' => $this->faker->sentence,
            'interaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
