<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sales>
 */
class SalesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderDate = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
        $dueDate = $this->faker->dateTimeBetween($orderDate, '+1 month')->format('Y-m-d H:i:s');

        return [
            'invoice' => 'INV-' . $this->faker->unique()->randomNumber(5),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'order_date' => $orderDate,
            'due_date' => $dueDate,
            'payment_type' => $this->faker->randomElement(['cash', 'card', 'transfer', 'ewallet']),
            'order_discount' => $this->faker->randomFloat(2, 0, 20),
            'order_discount_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['Paid', 'Partial', 'Unpaid']),
            'tax_rate' => $this->faker->randomFloat(2, 0, 15),
            'total_tax' => $this->faker->randomFloat(2, 0, 1000),
            'amount_received' => $this->faker->randomFloat(2, 0, 10000),
            'change_amount' => $this->faker->randomFloat(2, 0, 100),
            'is_pos' => $this->faker->boolean(),
            'sales_opportunity_id' => null, // Assuming this can be null
        ];
    }
}
