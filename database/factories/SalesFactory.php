<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sales;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sales>
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

        // Use the first active tax available; fall back to sensible defaults so
        // the factory works in unit tests where no DB taxes exist.
        $tax = Tax::where('is_active', true)->first();
        $taxId = $tax?->id;
        $taxRate = $tax ? (float) $tax->rate : 0.0;
        $taxType = $tax?->type ?? 'none';

        return [
            'invoice' => 'INV-'.$this->faker->unique()->randomNumber(5),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'order_date' => $orderDate,
            'due_date' => $dueDate,
            'payment_type' => $this->faker->randomElement(['cash', 'card', 'transfer', 'ewallet']),
            'order_discount' => $this->faker->randomFloat(2, 0, 20),
            'order_discount_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['Paid', 'Partial', 'Unpaid']),
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType,
            'total_tax' => $this->faker->randomFloat(2, 0, 1000),
            'amount_received' => $this->faker->randomFloat(2, 0, 10000),
            'change_amount' => $this->faker->randomFloat(2, 0, 100),
            'type' => $this->faker->randomElement([Sales::TYPE_REGULAR, Sales::TYPE_POS]),
            'sales_opportunity_id' => null,
            'warehouse_id' => Warehouse::factory(),
        ];
    }
}
