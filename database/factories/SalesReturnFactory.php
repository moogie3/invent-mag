<?php

namespace Database\Factories;

use App\Models\SalesReturn;
use App\Models\Sales;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReturnFactory extends Factory
{
    protected $model = SalesReturn::class;

    public function definition(): array
    {
        return [
            'sales_id' => Sales::factory(),
            'user_id' => User::factory(),
            'return_date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => $this->faker->randomElement(SalesReturn::$statuses),
        ];
    }
}
