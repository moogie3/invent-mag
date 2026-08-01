<?php

namespace Database\Factories;

use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReturnFactory extends Factory
{
    protected $model = SalesReturn::class;

    public function definition(): array
    {
        // Use the first active tax; fall back to safe defaults for unit tests.
        $tax = Tax::where('is_active', true)->first();
        $taxId = $tax?->id;
        $taxRate = $tax ? (float) $tax->rate : 0.0;
        $taxType = $tax?->type ?? 'none';

        $totalAmount = $this->faker->randomFloat(2, 10, 1000);
        $taxAmount = round($totalAmount * ($taxRate / 100), 2);

        return [
            'sales_id' => Sales::factory(),
            'user_id' => User::factory(),
            'return_date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(SalesReturn::$statuses),
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType,
            'tax_amount' => $taxAmount,
        ];
    }
}
