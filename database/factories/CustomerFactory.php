<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'phone_number' => $this->faker->phoneNumber(),
            'payment_terms' => $this->faker->randomElement(['15', '30', '45', '60']),
            'email' => $this->faker->unique()->safeEmail(),
            'image' => null,
        ];
    }
}