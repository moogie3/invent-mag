<?php

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'rate' => $this->faker->randomFloat(2, 0, 20),
            'type' => $this->faker->randomElement(['exclusive', 'inclusive', 'none']),
            'description' => $this->faker->optional()->sentence,
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
