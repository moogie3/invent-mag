<?php

namespace Database\Factories;

use App\Models\POItem;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $table = 'po';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderDate = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
        $dueDate = $this->faker->dateTimeBetween($orderDate, '+1 month')->format('Y-m-d H:i:s');

        // Use the first active tax available; fall back to sensible defaults so
        // the factory works in unit tests where no DB taxes exist.
        $tax = Tax::where('is_active', true)->first();
        $taxId = $tax?->id;
        $taxRate = $tax ? (float) $tax->rate : 0.0;
        $taxType = $tax?->type ?? 'none';

        return [
            'invoice' => 'PO-'.$this->faker->unique()->randomNumber(5),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_date' => $orderDate,
            'due_date' => $dueDate,
            'payment_type' => $this->faker->randomElement(['Cash', 'Transfer']),
            'discount_total' => $this->faker->randomFloat(2, 0, 50),
            'discount_total_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['Paid', 'Partial', 'Unpaid']),
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType,
            'total_tax' => 0,
        ];
    }

    public function hasItems(int $count = 1, array $attributes = []): static
    {
        return $this->has(POItem::factory()->count($count)->state($attributes), 'items');
    }

    public function forSupplier(Supplier $supplier)
    {
        return $this->state(function (array $attributes) use ($supplier) {
            return [
                'supplier_id' => $supplier->id,
            ];
        });
    }
}
