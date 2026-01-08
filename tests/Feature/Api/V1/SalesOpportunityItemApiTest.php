<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\SalesOpportunity;
use App\Models\SalesOpportunityItem;
use App\Models\SalesPipeline;
use App\Models\PipelineStage;
use App\Models\Product;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class SalesOpportunityItemApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private $customer;
    private $pipeline;
    private $stage;
    private $product;
    private $opportunity;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->pipeline = SalesPipeline::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->stage = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->pipeline->id,
            'position' => 1,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/sales-opportunity-items');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        SalesOpportunityItem::factory()->count(3)->create([
            'sales_opportunity_id' => $this->opportunity->id,
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales-opportunity-items');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sales_opportunity_id',
                        'product_id',
                        'quantity',
                        'price',
                        'sales_opportunity' => [
                            'id',
                            'name',
                        ],
                        'product' => [
                            'id',
                            'name',
                        ],
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $item = SalesOpportunityItem::factory()->create([
            'sales_opportunity_id' => $this->opportunity->id,
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson("/api/v1/sales-opportunity-items/{$item->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $item = SalesOpportunityItem::factory()->create([
            'sales_opportunity_id' => $this->opportunity->id,
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales-opportunity-items/{$item->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $item->id,
                    'sales_opportunity_id' => $this->opportunity->id,
                    'product_id' => $this->product->id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'sales_opportunity' => [
                        'id' => $this->opportunity->id,
                        'name' => $this->opportunity->name,
                    ],
                    'product' => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                    ],
                ]
            ]);
    }
}
