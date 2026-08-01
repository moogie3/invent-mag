<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Tax;
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
        // Use the first active tax; fall back to safe defaults for unit tests.
        $tax = Tax::where('is_active', true)->first();
        $taxId = $tax?->id;
        $taxRate = $tax ? (float) $tax->rate : 0.0;
        $taxType = $tax?->type ?? 'none';

        $totalAmount = $this->faker->randomFloat(2, 100, 10000);
        $taxAmount = round($totalAmount * ($taxRate / 100), 2);

        return [
            'purchase_id' => Purchase::factory(),
            'user_id' => User::factory(),
            'return_date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(PurchaseReturn::$statuses),
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType,
            'tax_amount' => $taxAmount,
        ];
    }
}
