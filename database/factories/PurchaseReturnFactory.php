<?php

namespace Database\Factories;

use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseReturnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PurchaseReturn::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'purchase_id' => Purchase::factory(),
            'user_id' => User::factory(),
            'return_date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(PurchaseReturn::$statuses),
        ];
    }
}
