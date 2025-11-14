<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
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
            'invoice' => 'PO-' . $this->faker->unique()->randomNumber(5),
            'supplier_id' => Supplier::factory(),
            'order_date' => $orderDate,
            'due_date' => $dueDate,
            'payment_type' => $this->faker->randomElement(['Cash', 'Transfer']),
            'discount_total' => $this->faker->randomFloat(2, 0, 50),
            'discount_total_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['Paid', 'Partial', 'Unpaid']),
        ];
    }

    public function hasItems(int $count = 1): static
    {
        return $this->has(POItemFactory::new()->count($count), 'items');
    }

    public function forSupplier(Supplier $supplier)
    {
        return $this->state(function (array $attributes) use ($supplier) {
            return [
                'supplier_id' => $supplier->id,
            ];
        });
    }
}
