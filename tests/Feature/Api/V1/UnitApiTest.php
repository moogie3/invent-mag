<?php

namespace Tests\Feature\Api\V1;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class UnitApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_user_cannot_access_units_index()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/units');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_units()
    {
        Unit::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/units');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'short_name',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_unit_show()
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        Auth::guard('web')->logout();
        $response = $this->getJson("/api/v1/units/{$unit->id}");
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_unit()
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/units/{$unit->id}");
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'short_name' => $unit->symbol,
                ]
            ]);
    }

    #[Test]
    public function user_without_permission_can_view_units()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/units')
            ->assertStatus(200);
    }

    #[Test]
    public function authenticated_user_can_create_unit()
    {
        $payload = [
            'name' => 'API Unit',
            'symbol' => 'APIU',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/units', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('units', [
            'tenant_id' => $this->tenant->id,
            'name' => 'API Unit',
        ]);
    }

    #[Test]
    public function unit_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/units', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'symbol']);
    }

    #[Test]
    public function authenticated_user_can_update_unit()
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $payload = ['name' => 'Updated Name', 'symbol' => 'UPD'];

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/units/{$unit->id}", $payload)
            ->assertStatus(200);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function authenticated_user_can_delete_unit()
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/units/{$unit->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }
}
