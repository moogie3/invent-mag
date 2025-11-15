<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesOpportunity;
use App\Models\SalesPipeline;
use App\Services\SalesPipelineService;
use Tests\Unit\BaseUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class SalesPipelineServiceTest extends BaseUnitTestCase
{
    protected SalesPipelineService $salesPipelineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesPipelineService = new SalesPipelineService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SalesPipelineService::class, $this->salesPipelineService);
    }

    #[Test]
    public function it_can_get_sales_pipeline_index_data()
    {
        SalesPipeline::factory()->count(2)->create();
        Customer::factory()->count(3)->create();

        $data = $this->salesPipelineService->getSalesPipelineIndexData();

        $this->assertArrayHasKey('pipelines', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertCount(2, $data['pipelines']);
        $this->assertCount(3, $data['customers']);
    }

    #[Test]
    public function it_can_create_a_pipeline()
    {
        $data = ['name' => 'New Pipeline'];
        $pipeline = $this->salesPipelineService->createPipeline($data);

        $this->assertInstanceOf(SalesPipeline::class, $pipeline);
        $this->assertDatabaseHas('sales_pipelines', ['name' => 'New Pipeline']);
    }

    #[Test]
    public function it_can_update_a_pipeline()
    {
        $pipeline = SalesPipeline::factory()->create(['name' => 'Old Name']);
        $data = ['name' => 'Updated Name'];
        $this->salesPipelineService->updatePipeline($pipeline, $data);

        $this->assertDatabaseHas('sales_pipelines', ['id' => $pipeline->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function it_can_delete_a_pipeline()
    {
        $pipeline = SalesPipeline::factory()->create();
        $this->salesPipelineService->deletePipeline($pipeline);

        $this->assertDatabaseMissing('sales_pipelines', ['id' => $pipeline->id]);
    }

    #[Test]
    public function it_can_create_a_stage()
    {
        $pipeline = SalesPipeline::factory()->create();
        $data = ['name' => 'New Stage', 'color' => '#FF0000'];
        $stage = $this->salesPipelineService->createStage($pipeline, $data);

        $this->assertInstanceOf(PipelineStage::class, $stage);
        $this->assertDatabaseHas('pipeline_stages', ['name' => 'New Stage', 'sales_pipeline_id' => $pipeline->id]);
    }

    #[Test]
    public function it_can_update_a_stage()
    {
        $stage = PipelineStage::factory()->create(['name' => 'Old Stage Name']);
        $data = ['name' => 'Updated Stage Name'];
        $this->salesPipelineService->updateStage($stage, $data);

        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage->id, 'name' => 'Updated Stage Name']);
    }

    #[Test]
    public function it_can_delete_a_stage()
    {
        $stage = PipelineStage::factory()->create();
        $this->salesPipelineService->deleteStage($stage);

        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    #[Test]
    public function it_can_reorder_stages()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage1 = PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 0]);
        $stage2 = PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 1]);

        $reorderedStages = [
            ['id' => $stage2->id, 'position' => 0],
            ['id' => $stage1->id, 'position' => 1],
        ];

        $this->salesPipelineService->reorderStages($pipeline, $reorderedStages);

        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage2->id, 'position' => 0]);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage1->id, 'position' => 1]);
    }

    #[Test]
    public function it_can_create_an_opportunity()
    {
        $customer = Customer::factory()->create();
        $pipeline = SalesPipeline::factory()->create();
        $stage = PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);
        $product = Product::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'New Opportunity',
            'description' => 'Opportunity description',
            'expected_close_date' => now()->addMonth()->toDateString(),
            'status' => 'open',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 100],
            ],
        ];

        $opportunity = $this->salesPipelineService->createOpportunity($data);

        $this->assertInstanceOf(SalesOpportunity::class, $opportunity);
        $this->assertDatabaseHas('sales_opportunities', ['name' => 'New Opportunity', 'amount' => 200]);
        $this->assertDatabaseHas('sales_opportunity_items', ['sales_opportunity_id' => $opportunity->id, 'product_id' => $product->id]);
    }

    #[Test]
    public function it_can_update_an_opportunity()
    {
        $opportunity = SalesOpportunity::factory()->hasItems(1)->create(['amount' => 100]);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'sales_pipeline_id' => $opportunity->sales_pipeline_id,
            'pipeline_stage_id' => $opportunity->pipeline_stage_id,
            'name' => 'Updated Opportunity',
            'description' => 'Updated description',
            'expected_close_date' => now()->addMonths(2)->toDateString(),
            'status' => 'negotiation',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'price' => 150],
            ],
        ];

        $this->salesPipelineService->updateOpportunity($opportunity, $data);

        $this->assertDatabaseHas('sales_opportunities', ['id' => $opportunity->id, 'name' => 'Updated Opportunity', 'amount' => 450]);
        $this->assertDatabaseHas('sales_opportunity_items', ['sales_opportunity_id' => $opportunity->id, 'product_id' => $product->id]);
    }

    #[Test]
    public function it_can_delete_an_opportunity()
    {
        $opportunity = SalesOpportunity::factory()->create();
        $this->salesPipelineService->deleteOpportunity($opportunity);

        $this->assertDatabaseMissing('sales_opportunities', ['id' => $opportunity->id]);
    }

    #[Test]
    public function it_can_move_an_opportunity()
    {
        $opportunity = SalesOpportunity::factory()->create();
        $newStage = PipelineStage::factory()->create(['sales_pipeline_id' => $opportunity->sales_pipeline_id]);

        $this->salesPipelineService->moveOpportunity($opportunity, $newStage->id);

        $this->assertDatabaseHas('sales_opportunities', ['id' => $opportunity->id, 'pipeline_stage_id' => $newStage->id]);
    }

    #[Test]
    public function it_can_convert_an_opportunity_to_a_sales_order()
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $opportunity = SalesOpportunity::factory()->hasItems(1, ['quantity' => 2, 'price' => 100])->create(['status' => 'won']);
        Product::find($opportunity->items->first()->product_id)->update(['stock_quantity' => 10]);

        $salesOrder = $this->salesPipelineService->convertToSalesOrder($opportunity);

        $this->assertInstanceOf(Sales::class, $salesOrder);
        $this->assertDatabaseHas('sales', ['sales_opportunity_id' => $opportunity->id]);
        $this->assertDatabaseHas('sales_opportunities', ['id' => $opportunity->id, 'status' => 'converted', 'sales_id' => $salesOrder->id]);
        $this->assertEquals(8, Product::find($opportunity->items->first()->product_id)->stock_quantity);
    }

    #[Test]
    public function it_throws_an_exception_if_opportunity_is_already_converted()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This opportunity has already been converted to a sales order.');

        $sales = Sales::factory()->create();
        $opportunity = SalesOpportunity::factory()->create(['status' => 'converted', 'sales_id' => $sales->id]);
        $this->salesPipelineService->convertToSalesOrder($opportunity);
    }

    #[Test]
    public function it_throws_an_exception_if_opportunity_is_not_won()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Opportunity must be in a "won" status to be converted.');

        $opportunity = SalesOpportunity::factory()->create(['status' => 'open']);
        $this->salesPipelineService->convertToSalesOrder($opportunity);
    }

    #[Test]
    public function it_throws_an_exception_if_no_products_in_opportunity()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No products associated with this opportunity.');

        $opportunity = SalesOpportunity::factory()->create(['status' => 'won']);
        $this->salesPipelineService->convertToSalesOrder($opportunity);
    }

    #[Test]
    public function it_throws_an_exception_if_insufficient_stock()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient stock for product:/');

        $this->actingAs(\App\Models\User::factory()->create());
        $opportunity = SalesOpportunity::factory()->hasItems(1, ['quantity' => 10, 'price' => 100])->create(['status' => 'won']);
        Product::find($opportunity->items->first()->product_id)->update(['stock_quantity' => 5]);

        $this->salesPipelineService->convertToSalesOrder($opportunity);
    }

    #[Test]
    public function it_rolls_back_stock_when_deleting_a_converted_opportunity()
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $opportunity = SalesOpportunity::factory()->hasItems(1, ['quantity' => 3, 'price' => 100])->create(['status' => 'won']);
        $product = Product::find($opportunity->items->first()->product_id);
        $product->update(['stock_quantity' => 10]);

        $salesOrder = $this->salesPipelineService->convertToSalesOrder($opportunity);
        $this->assertEquals(7, $product->fresh()->stock_quantity);

        $this->salesPipelineService->deleteOpportunity($opportunity->fresh());

        $this->assertDatabaseMissing('sales', ['id' => $salesOrder->id]);
        $this->assertDatabaseMissing('sales_opportunities', ['id' => $opportunity->id]);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }
}
