<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOpportunity;
use App\Models\SalesOpportunityItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOpportunityItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalesOpportunityItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'sales_opportunity_id' => SalesOpportunity::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
