<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SalesPipeline;
use App\Models\PipelineStage;
use Spatie\Permission\Models\Role;

class SalesPipelineControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $pipeline;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->pipeline = SalesPipeline::factory()->create();
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/sales-pipelines');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        SalesPipeline::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales-pipelines');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'stages' => [
                            '*' => [
                                'id',
                                'name',
                                'position',
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_store_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->postJson('/api/v1/sales-pipelines', []);

        $response->assertUnauthorized();
    }

    public function test_store_creates_a_new_sales_pipeline()
    {
        $pipelineData = [
            'name' => 'New Sales Pipeline',
            'description' => 'A description for the new pipeline.',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/sales-pipelines', $pipelineData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'New Sales Pipeline',
                ]
            ]);

        $this->assertDatabaseHas('sales_pipelines', [
            'name' => 'New Sales Pipeline',
        ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson("/api/v1/sales-pipelines/{$this->pipeline->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales-pipelines/{$this->pipeline->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->pipeline->id,
                    'name' => $this->pipeline->name,
                ]
            ]);
    }

    public function test_update_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->putJson("/api/v1/sales-pipelines/{$this->pipeline->id}", ['name' => 'Updated Pipeline']);

        $response->assertUnauthorized();
    }

    public function test_update_modifies_an_existing_sales_pipeline()
    {
        $newName = 'Updated Sales Pipeline';

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/sales-pipelines/{$this->pipeline->id}", [
            'name' => $newName,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $newName
                ]
            ]);

        $this->assertDatabaseHas('sales_pipelines', [
            'id' => $this->pipeline->id,
            'name' => $newName,
        ]);
    }

    public function test_destroy_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->deleteJson("/api/v1/sales-pipelines/{$this->pipeline->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_a_sales_pipeline()
    {
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/sales-pipelines/{$this->pipeline->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('sales_pipelines', [
            'id' => $this->pipeline->id,
        ]);
    }

    public function test_store_stage_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->postJson("/api/v1/sales-pipelines/{$this->pipeline->id}/stages", ['name' => 'New Stage']);

        $response->assertUnauthorized();
    }

    public function test_store_stage_creates_a_new_pipeline_stage()
    {
        $stageData = [
            'name' => 'New Pipeline Stage',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/sales-pipelines/{$this->pipeline->id}/stages", $stageData);

        $response->assertCreated()
            ->assertJson([
                'name' => 'New Pipeline Stage',
            ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'sales_pipeline_id' => $this->pipeline->id,
            'name' => 'New Pipeline Stage',
        ]);
    }

    public function test_reorder_stages_returns_unauthorized_if_user_is_not_authenticated()
    {
        $stage1 = PipelineStage::factory()->create(['sales_pipeline_id' => $this->pipeline->id, 'position' => 1]);
        $stage2 = PipelineStage::factory()->create(['sales_pipeline_id' => $this->pipeline->id, 'position' => 2]);

        $response = $this->postJson("/api/v1/sales-pipelines/{$this->pipeline->id}/stages/reorder", ['stages' => [
            ['id' => $stage2->id, 'position' => 1],
            ['id' => $stage1->id, 'position' => 2],
        ]]);

        $response->assertUnauthorized();
    }

    public function test_reorder_stages_modifies_pipeline_stage_order()
    {
        $stage1 = PipelineStage::factory()->create(['sales_pipeline_id' => $this->pipeline->id, 'position' => 1]);
        $stage2 = PipelineStage::factory()->create(['sales_pipeline_id' => $this->pipeline->id, 'position' => 2]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/sales-pipelines/{$this->pipeline->id}/stages/reorder", [
            'stages' => [
                ['id' => $stage2->id, 'position' => 1],
                ['id' => $stage1->id, 'position' => 2],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Stages reordered successfully'
            ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage1->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage2->id,
            'position' => 1,
        ]);
    }
}
