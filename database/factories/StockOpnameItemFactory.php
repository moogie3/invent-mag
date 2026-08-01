<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameItemFactory extends Factory
{
    protected $model = StockOpnameItem::class;

    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'product_id' => Product::factory(),
            'expected_qty' => 0,
            'counted_qty' => null,
            'variance' => null,
            'variance_value' => null,
        ];
    }

    public function withCount(int $countedQty, int $expectedQty = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_qty' => $expectedQty,
            'counted_qty' => $countedQty,
            'variance' => $countedQty - $expectedQty,
            'variance_value' => abs($countedQty - $expectedQty) * 5000,
        ]);
    }
}
