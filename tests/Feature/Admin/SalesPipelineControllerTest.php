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
}
