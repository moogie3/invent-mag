<?php

namespace Database\Factories;

use App\Models\SalesReturnItem;
use App\Models\SalesReturn;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReturnItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalesReturnItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 10, 100);
        $total = $quantity * $price;

        return [
            'sales_return_id' => SalesReturn::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'total' => $total,
        ];
    }
}
