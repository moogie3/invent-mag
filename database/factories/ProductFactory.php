<?php

namespace Database\Factories;

use App\Models\Categories;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
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
            'barcode' => $this->faker->unique()->ean13,
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'image' => null,
            'price' => $this->faker->randomFloat(2, 1000, 100000),
            'selling_price' => $this->faker->randomFloat(2, 1500, 150000),
            // 'stock_quantity' => $this->faker->numberBetween(0, 1000), // Removed for multi-warehouse
            'category_id' => Categories::factory(),
            'supplier_id' => Supplier::factory(),
            'units_id' => Unit::factory(),
            'has_expiry' => $this->faker->boolean,
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
            // 'warehouse_id' => Warehouse::factory(), // Removed for multi-warehouse
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            // Check if stock quantity was passed as a temporary attribute
            $quantity = $product->stock_quantity_temp ?? $this->faker->numberBetween(0, 1000);
            
            // Attach to a warehouse with some stock
            $warehouse = Warehouse::inRandomOrder()->first() ?? Warehouse::factory()->create();
            \App\Models\ProductWarehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'tenant_id' => $product->tenant_id,
            ]);
        });
    }

    /**
     * Define a state for specific stock quantity.
     */
    public function withStock(int $quantity)
    {
        return $this->state(function (array $attributes) use ($quantity) {
            return [
                'stock_quantity_temp' => $quantity,
            ];
        });
    }
}
