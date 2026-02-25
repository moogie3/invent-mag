<?php

namespace Database\Factories;

use App\Models\Categories;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
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
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'category_id' => Categories::factory(),
            'supplier_id' => Supplier::factory(),
            'units_id' => Unit::factory(),
            'has_expiry' => $this->faker->boolean,
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
            'warehouse_id' => Warehouse::factory(),
        ];
    }
}
