<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->ean8,
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'phone_number' => $this->faker->phoneNumber,
            'location' => $this->faker->randomElement(['IN', 'OUT']),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'On Delivery']),
            'email' => $this->faker->unique()->safeEmail,
            'image' => null,
        ];
    }
}
