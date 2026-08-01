<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
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
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'meeting', 'note']),
            'notes' => $this->faker->paragraph,
            'interaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
