<?php

namespace Database\Factories;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\POItem>
 */
class POItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $price = $this->faker->randomFloat(2, 10, 200);
        $total = $quantity * $price;
        $expiryDate = $this->faker->boolean(70) ? $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s') : null;

        return [
            'po_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $this->faker->randomFloat(2, 0, 10),
            'discount_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'total' => $total,
            'expiry_date' => $expiryDate,
            'remaining_quantity' => $quantity,
        ];
    }

    public function forPurchase(Purchase $purchase)
    {
        return $this->state(function (array $attributes) use ($purchase) {
            return [
                'po_id' => $purchase->id,
            ];
        });
    }
}
