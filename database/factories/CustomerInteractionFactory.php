<?php

namespace Database\Factories;

use App\Models\CustomerInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInteractionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CustomerInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'meeting', 'note']),
            'notes' => $this->faker->paragraph,
            'interaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}