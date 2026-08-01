<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesItem>
 */
class SalesItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $product = Product::factory()->withStock(1000)->create();
        $customerPrice = $product->selling_price;
        $total = $quantity * $customerPrice;

        return [
            'sales_id' => Sales::factory(), // Will be overridden by test
            'product_id' => $product->id,
            'quantity' => $quantity,
            'discount' => 0,
            'discount_type' => 'fixed',
            'customer_price' => $customerPrice,
            'total' => $total,
        ];
    }
}
