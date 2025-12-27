<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalesOpportunity;
use App\Models\SalesOpportunityItem;
use App\Models\SalesPipeline;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PipelineStage;

class SalesOpportunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pipeline = SalesPipeline::where('is_default', true)->first();
        $customers = Customer::all();
        $products = Product::all();
        $stages = PipelineStage::all();

        if (!$pipeline || $customers->isEmpty() || $products->isEmpty() || $stages->isEmpty()) {
            $this->command->info('Skipping SalesOpportunitySeeder: Default pipeline, customers, products, or stages not found.');
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $status = ($i == 0) ? 'won' : 'open';
            $opportunity = SalesOpportunity::create([
                'customer_id' => $customers->random()->id,
                'sales_pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stages->random()->id,
                'name' => 'Opportunity ' . ($i + 1),
                'amount' => rand(1000, 10000),
                'status' => $status,
            ]);

            for ($j = 0; $j < rand(1, 3); $j++) {
                $product = $products->random();
                SalesOpportunityItem::create([
                    'sales_opportunity_id' => $opportunity->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 5),
                    'price' => $product->selling_price,
                ]);
            }
        }
    }
}
