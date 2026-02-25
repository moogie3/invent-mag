<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_date' => $this->faker->dateTimeThisMonth(),
            'payment_method' => $this->faker->randomElement(['Cash', 'Card', 'Transfer']),
            'notes' => $this->faker->sentence,
            'paymentable_id' => 1, // Default values, will be overridden in tests
            'paymentable_type' => 'App\Models\Purchase', // Default values, will be overridden in tests
        ];
    }
}
