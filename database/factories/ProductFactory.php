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
     * Stock quantity to be set after creation.
     */
    protected ?int $stockQuantity = null;

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
            'category_id' => Categories::factory(),
            'supplier_id' => Supplier::factory(),
            'units_id' => Unit::factory(),
            'has_expiry' => $this->faker->boolean,
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
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
            // Use the specified stock quantity or generate a random one
            $quantity = $this->stockQuantity ?? $this->faker->numberBetween(0, 1000);
            
            // Prefer main warehouse, then any existing, or create a main one
            $warehouse = Warehouse::where('is_main', true)->first()
                ?? Warehouse::inRandomOrder()->first()
                ?? Warehouse::factory()->create(['is_main' => true]);
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
     * 
     * @param int $quantity The stock quantity to set
     * @return static
     */
    public function withStock(int $quantity): static
    {
        return $this->afterCreating(function (Product $product) use ($quantity) {
            // Update the product warehouse quantity that was created in configure()
            $productWarehouse = $product->productWarehouses()->first();
            if ($productWarehouse) {
                $productWarehouse->update(['quantity' => $quantity]);
            }
        });
    }

    /**
     * Create a product with no stock (zero quantity).
     * 
     * @return static
     */
    public function withNoStock(): static
    {
        return $this->withStock(0);
    }
}
