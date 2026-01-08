<?php

namespace Tests\Feature\Api\V1;

use App\Models\PipelineStage;
use App\Models\SalesPipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class PipelineStageApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;
    private SalesPipeline $salesPipeline;
    private PipelineStage $pipelineStage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $permissions = ['edit-pipeline-stages', 'delete-pipeline-stages'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'api');
        }

        $this->user->givePermissionTo(['edit-pipeline-stages', 'delete-pipeline-stages']);
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->salesPipeline = SalesPipeline::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->pipelineStage = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->salesPipeline->id,
            'position' => $this->salesPipeline->stages()->count(), // Ensure unique position
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_pipeline_stages_api()
    {
        Auth::guard('web')->logout();
        $this->putJson("/api/v1/pipeline-stages/{$this->pipelineStage->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/pipeline-stages/{$this->pipelineStage->id}")->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_update_pipeline_stage()
    {
        $response = $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/pipeline-stages/{$this->pipelineStage->id}", []);
        $response->assertStatus(403);
    }

    #[Test]
    public function user_without_permission_cannot_delete_pipeline_stage()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/pipeline-stages/{$this->pipelineStage->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_update_pipeline_stage()
    {
        $stageToUpdate = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->salesPipeline->id,
            'position' => $this->salesPipeline->stages()->count() + 1, // Ensure unique position
        ]);

        $payload = [
            'name' => 'Updated Stage Name',
            'position' => 1,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/pipeline-stages/{$stageToUpdate->id}", $payload)
            ->assertStatus(200);

        $freshStage = $stageToUpdate->fresh();

        $this->assertEquals('Updated Stage Name', $freshStage->name);
        $this->assertEquals(1, $freshStage->position);
    }

    #[Test]
    public function authenticated_user_can_delete_pipeline_stage()
    {
        $pipelineStageToDelete = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->salesPipeline->id,
            'position' => $this->salesPipeline->stages()->count() + 2, // Ensure unique position
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/pipeline-stages/{$pipelineStageToDelete->id}")
            ->assertStatus(204);

        $this->assertNull(PipelineStage::find($pipelineStageToDelete->id));
    }

    #[Test]
    public function pipeline_stage_api_returns_validation_errors()
    {
        $stageForValidation = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->salesPipeline->id,
            'position' => $this->salesPipeline->stages()->count() + 3, // Ensure unique position
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/pipeline-stages/{$stageForValidation->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'position',
            ]);
    }
}
