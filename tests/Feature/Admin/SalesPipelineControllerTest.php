<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\SalesPipeline;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPipelineControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        \App\Models\Tax::factory()->create(['is_active' => true, 'rate' => 10]); // Ensure an active tax exists
    }

    public function test_it_can_display_the_sales_pipeline_index_page()
    {
        $response = $this->get(route('admin.sales_pipeline.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.pipeline');
    }

    public function test_it_can_store_a_new_pipeline()
    {
        $pipelineData = [
            'name' => 'New Pipeline',
            'description' => 'A new sales pipeline.',
            'is_default' => false,
        ];

        $response = $this->postJson(route('admin.sales_pipeline.pipelines.store'), $pipelineData);

        $response->assertStatus(201)
            ->assertJsonFragment($pipelineData);

        $this->assertDatabaseHas('sales_pipelines', $pipelineData);
    }

    public function test_it_can_update_a_pipeline()
    {
        $pipeline = SalesPipeline::factory()->create();

        $updateData = [
            'name' => 'Updated Pipeline Name',
            'description' => 'Updated pipeline description.',
            'is_default' => true,
        ];

        $response = $this->putJson(route('admin.sales_pipeline.pipelines.update', $pipeline), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('sales_pipelines', $updateData);
    }

    public function test_it_can_delete_a_pipeline()
    {
        $pipeline = SalesPipeline::factory()->create();

        $response = $this->delete(route('admin.sales_pipeline.pipelines.destroy', $pipeline));

        $response->assertRedirect(route('admin.sales_pipeline.index'));
        $response->assertSessionHas('success', 'Pipeline deleted successfully!');

        $this->assertDatabaseMissing('sales_pipelines', ['id' => $pipeline->id]);
    }

    public function test_it_can_store_a_new_stage()
    {
        $pipeline = SalesPipeline::factory()->create();

        $stageData = [
            'name' => 'New Stage',
            'is_closed' => false,
        ];

        $response = $this->postJson(route('admin.sales_pipeline.stages.store', $pipeline), $stageData);

        $response->assertStatus(201)
            ->assertJsonFragment($stageData);

        $this->assertDatabaseHas('pipeline_stages', [
            'sales_pipeline_id' => $pipeline->id,
            'name' => 'New Stage',
            'is_closed' => false,
        ]);
    }

    public function test_it_can_update_a_stage()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);

        $updateData = [
            'name' => 'Updated Stage Name',
            'position' => 1,
            'is_closed' => true,
        ];

        $response = $this->putJson(route('admin.sales_pipeline.stages.update', $stage), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('pipeline_stages', $updateData);
    }

    public function test_it_can_delete_a_stage()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);

        $response = $this->delete(route('admin.sales_pipeline.stages.destroy', $stage));

        $response->assertRedirect(route('admin.sales_pipeline.index'));
        $response->assertSessionHas('success', 'Stage deleted successfully!');

        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_it_can_store_a_new_opportunity()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);
        $customer = \App\Models\Customer::factory()->create();
        $product = \App\Models\Product::factory()->create();

        $opportunityData = [
            'customer_id' => $customer->id,
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'New Opportunity',
            'description' => 'A new sales opportunity.',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'status' => 'open',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ];

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.store'), $opportunityData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Opportunity']);

        $this->assertDatabaseHas('sales_opportunities', ['name' => 'New Opportunity']);
    }

    public function test_it_can_update_an_opportunity()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create();
        $product = \App\Models\Product::factory()->create();

        $updateData = [
            'customer_id' => $opportunity->customer_id,
            'sales_pipeline_id' => $opportunity->sales_pipeline_id,
            'pipeline_stage_id' => $opportunity->pipeline_stage_id,
            'name' => 'Updated Opportunity Name',
            'description' => 'Updated opportunity description.',
            'expected_close_date' => now()->addDays(15)->format('Y-m-d'),
            'status' => 'won',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 200,
                ],
            ],
        ];

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.update', $opportunity), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Opportunity Name']);

        $this->assertDatabaseHas('sales_opportunities', ['name' => 'Updated Opportunity Name']);
    }

    public function test_it_can_delete_an_opportunity()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create();

        $response = $this->delete(route('admin.sales_pipeline.opportunities.destroy', $opportunity));

        $response->assertRedirect(route('admin.sales_pipeline.index'));
        $response->assertSessionHas('success', 'Opportunity deleted successfully!');

        $this->assertDatabaseMissing('sales_opportunities', ['id' => $opportunity->id]);
    }

    public function test_store_pipeline_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Required
            'description' => 'A new sales pipeline.',
            'is_default' => 'not-a-boolean', // Invalid type
        ];

        $response = $this->postJson(route('admin.sales_pipeline.pipelines.store'), $invalidData);

        $response->assertStatus(422) // Unprocessable Entity
            ->assertJsonValidationErrors(['name', 'is_default']);
    }

    public function test_update_pipeline_with_invalid_data_returns_validation_errors()
    {
        $pipeline = SalesPipeline::factory()->create();

        $invalidData = [
            'name' => '', // Required
            'description' => 'Updated pipeline description.',
            'is_default' => 'not-a-boolean', // Invalid type
        ];

        $response = $this->putJson(route('admin.sales_pipeline.pipelines.update', $pipeline), $invalidData);

        $response->assertStatus(422) // Unprocessable Entity
            ->assertJsonValidationErrors(['name', 'is_default']);
    }

    public function test_it_can_list_all_pipelines()
    {
        SalesPipeline::factory()->count(3)->create();

        $response = $this->getJson(route('admin.sales_pipeline.pipelines.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_it_can_reorder_stages()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage1 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 0]);
        $stage2 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 1]);
        $stage3 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 2]);

        $reorderData = [
            'stages' => [
                ['id' => $stage3->id, 'position' => 0],
                ['id' => $stage1->id, 'position' => 1],
                ['id' => $stage2->id, 'position' => 2],
            ],
        ];

        $response = $this->postJson(route('admin.sales_pipeline.stages.reorder', $pipeline), $reorderData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Stages reordered successfully']);

        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage3->id, 'position' => 0]);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage1->id, 'position' => 1]);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage2->id, 'position' => 2]);
    }

    public function test_it_cannot_reorder_stages_with_invalid_data()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage1 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 0]);

        $invalidReorderData = [
            'stages' => [
                ['id' => 999, 'position' => 0], // Non-existent stage ID
                ['id' => $stage1->id, 'position' => -1], // Invalid position
            ],
        ];

        $response = $this->postJson(route('admin.sales_pipeline.stages.reorder', $pipeline), $invalidReorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stages.0.id', 'stages.1.position']);
    }

    public function test_it_can_list_opportunities()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);
        \App\Models\SalesOpportunity::factory()->count(3)->create([
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
        ]);

        $response = $this->getJson(route('admin.sales_pipeline.opportunities.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'opportunities');
    }

    public function test_it_can_show_an_opportunity()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create();

        $response = $this->getJson(route('admin.sales_pipeline.opportunities.show', $opportunity));

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $opportunity->name]);
    }

    public function test_it_can_move_an_opportunity()
    {
        $pipeline = SalesPipeline::factory()->create();
        $stage1 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 0]);
        $stage2 = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id, 'position' => 1]);
        $opportunity = \App\Models\SalesOpportunity::factory()->create([
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage1->id,
        ]);

        $moveData = [
            'pipeline_stage_id' => $stage2->id,
        ];

        $response = $this->putJson(route('admin.sales_pipeline.opportunities.move', $opportunity), $moveData);

        $response->assertStatus(200)
            ->assertJsonFragment(['pipeline_stage_id' => $stage2->id]);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'pipeline_stage_id' => $stage2->id,
        ]);
    }

    public function test_it_can_show_convert_form_for_opportunity()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create();

        $response = $this->get(route('admin.sales_pipeline.opportunities.convert.show', $opportunity));

        $response->assertStatus(200)
            ->assertViewIs('admin.layouts.modals.sales-pipeline-modals')
            ->assertViewHas('opportunity', $opportunity);
    }

    public function test_it_can_convert_opportunity_to_sales_order()
    {
        $product = \App\Models\Product::factory()->create(['stock_quantity' => 100]);
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);
        $opportunity = \App\Models\SalesOpportunity::factory()->create([
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'won',
        ]);
        $opportunity->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 50,
        ]);

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.convert', $opportunity));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Opportunity successfully converted to Sales Order.',
                'type' => 'success',
            ]);

        $this->assertDatabaseHas('sales', [
            'sales_opportunity_id' => $opportunity->id,
            'customer_id' => $opportunity->customer_id,
            'total' => ($opportunity->items->first()->quantity * $opportunity->items->first()->price) * (1 + (\App\Models\Tax::where('is_active', 1)->first()->rate / 100)), // Assuming default tax
        ]);

        $this->assertDatabaseHas('sales_items', [
            'sales_id' => \App\Models\Sales::where('sales_opportunity_id', $opportunity->id)->first()->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 95, // 100 - 5
        ]);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'status' => 'converted',
            'sales_id' => \App\Models\Sales::where('sales_opportunity_id', $opportunity->id)->first()->id,
        ]);
    }

    public function test_it_cannot_convert_already_converted_opportunity()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create([
            'status' => 'converted',
            'sales_id' => \App\Models\Sales::factory()->create()->id,
        ]);

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.convert', $opportunity));

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Failed to convert opportunity: This opportunity has already been converted to a sales order.']);
    }

    public function test_it_cannot_convert_opportunity_not_in_won_status()
    {
        $opportunity = \App\Models\SalesOpportunity::factory()->create([
            'status' => 'open', // Not 'won'
        ]);

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.convert', $opportunity));

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Failed to convert opportunity: Opportunity must be in a "won" status to be converted.']);
    }

    public function test_it_cannot_convert_opportunity_with_insufficient_stock()
    {
        $product = \App\Models\Product::factory()->create(['stock_quantity' => 2]); // Insufficient stock
        $pipeline = SalesPipeline::factory()->create();
        $stage = \App\Models\PipelineStage::factory()->create(['sales_pipeline_id' => $pipeline->id]);
        $opportunity = \App\Models\SalesOpportunity::factory()->create([
            'sales_pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'won',
        ]);
        $opportunity->items()->create([
            'product_id' => $product->id,
            'quantity' => 5, // More than available stock
            'price' => 50,
        ]);

        $response = $this->postJson(route('admin.sales_pipeline.opportunities.convert', $opportunity));

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Failed to convert opportunity: Insufficient stock for product: ' . $product->name . '. Available: 2, Requested: 5']);
    }
}
