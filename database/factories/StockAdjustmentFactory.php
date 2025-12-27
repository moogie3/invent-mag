<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockAdjustment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $quantityBefore = $this->faker->numberBetween(100, 200);
        $adjustmentAmount = $this->faker->numberBetween(1, 20);
        $adjustmentType = $this->faker->randomElement(['increase', 'decrease']);
        $quantityAfter = $adjustmentType === 'increase'
            ? $quantityBefore + $adjustmentAmount
            : $quantityBefore - $adjustmentAmount;

        return [
            'product_id' => Product::factory(),
            'adjustment_type' => $adjustmentType,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'adjustment_amount' => $adjustmentAmount,
            'reason' => $this->faker->sentence,
            'adjusted_by' => User::factory(),
        ];
    }
}
