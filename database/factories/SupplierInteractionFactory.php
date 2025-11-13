<?php

namespace Database\Factories;

use App\Models\SupplierInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInteractionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupplierInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'supplier_id' => \App\Models\Supplier::factory(),
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'meeting', 'note']),
            'notes' => $this->faker->paragraph,
            'interaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
