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

class SalesOpportunityControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $customer;
    private $pipeline;
    private $stage;
    private $product;
    private $opportunity; // Add opportunity for sales opportunity items

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->customer = Customer::factory()->create();
        $this->pipeline = SalesPipeline::factory()->create();
        $this->stage = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->pipeline->id,
            'position' => 1, // Ensure unique position
        ]);
        $this->product = \App\Models\Product::factory()->create();

        // Configure accounting settings for the user
        $cashAccount = \App\Models\Account::factory()->create(['name' => 'Cash Account for Test', 'code' => '1001']);
        $accountsReceivableAccount = \App\Models\Account::factory()->create(['name' => 'Accounts Receivable for Test', 'code' => '1002']);
        $salesRevenueAccount = \App\Models\Account::factory()->create(['name' => 'Sales Revenue for Test', 'code' => '1003']);
        $costOfGoodsSoldAccount = \App\Models\Account::factory()->create(['name' => 'Cost of Goods Sold for Test', 'code' => '1004']);
        $inventoryAccount = \App\Models\Account::factory()->create(['name' => 'Inventory for Test', 'code' => '1005']);

        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id,
            'accounts_receivable_account_id' => $accountsReceivableAccount->id,
            'sales_revenue_account_id' => $salesRevenueAccount->id,
            'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            'inventory_account_id' => $inventoryAccount->id,
        ];
        $this->user->save();

        $this->opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
            'amount' => 100.00,
            'status' => 'won', // Set status to 'won' for conversion tests
        ]);
        $this->opportunity->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/sales-opportunities');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales-opportunities');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_id',
                        'sales_pipeline_id',
                        'pipeline_stage_id',
                        'name',
                        'expected_close_date',
                        'amount',
                        'status',
                    ]
                ],
            ]);
    }

    public function test_store_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->postJson('/api/v1/sales-opportunities', []);

        $response->assertUnauthorized();
    }

    public function test_store_creates_a_new_sales_opportunity()
    {
        $opportunityData = [
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
            'name' => 'New Sales Opportunity',
            'expected_close_date' => now()->addMonths(1)->format('Y-m-d'),
            'amount' => 100.00, // This will be recalculated by the service
            'probability' => 50,
            'status' => 'open', // Changed to lowercase 'open'
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/sales-opportunities', $opportunityData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'New Sales Opportunity',
                    'amount' => 100.00,
                ]
            ]);

        $this->assertDatabaseHas('sales_opportunities', [
            'name' => 'New Sales Opportunity',
            'amount' => 100.00, // This will be recalculated by the service and should be 100.00
        ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->getJson("/api/v1/sales-opportunities/{$opportunity->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales-opportunities/{$opportunity->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $opportunity->id,
                    'name' => $opportunity->name,
                ]
            ]);
    }

    public function test_update_returns_unauthorized_if_user_is_not_authenticated()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->putJson("/api/v1/sales-opportunities/{$opportunity->id}", ['amount' => 2000.00]);

        $response->assertUnauthorized();
    }

    public function test_update_modifies_an_existing_sales_opportunity()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
            'amount' => 100.00, // Initial amount matches item price * quantity
            'name' => 'Existing Opportunity',
            'expected_close_date' => now()->addMonths(2),
            'status' => 'open',
        ]);
        $opportunity->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        $newAmount = 200.00; // New amount based on updated quantity

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/sales-opportunities/{$opportunity->id}", [
            'customer_id' => $opportunity->customer_id,
            'sales_pipeline_id' => $opportunity->sales_pipeline_id,
            'pipeline_stage_id' => $opportunity->pipeline_stage_id,
            'name' => $opportunity->name,
            'expected_close_date' => $opportunity->expected_close_date->format('Y-m-d'),
            // 'amount' => $newAmount, // The amount will be recalculated from items
            'probability' => $opportunity->probability,
            'status' => 'open', // Ensure valid status
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2, // Change quantity to affect total amount
                    'price' => 100.00,
                ]
            ]
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'amount' => $newAmount
                ]
            ]);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'amount' => $newAmount,
        ]);
    }

    public function test_destroy_returns_unauthorized_if_user_is_not_authenticated()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->deleteJson("/api/v1/sales-opportunities/{$opportunity->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_a_sales_opportunity()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/sales-opportunities/{$opportunity->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('sales_opportunities', [
            'id' => $opportunity->id,
        ]);
    }

    public function test_move_returns_unauthorized_if_user_is_not_authenticated()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);
        $newStage = PipelineStage::factory()->create(['sales_pipeline_id' => $this->pipeline->id]);

        $response = $this->putJson("/api/v1/sales-opportunities/{$opportunity->id}/move", ['pipeline_stage_id' => $newStage->id]);

        $response->assertUnauthorized();
    }

    public function test_move_sales_opportunity_to_new_stage()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);
        $newStage = PipelineStage::factory()->create([
            'sales_pipeline_id' => $this->pipeline->id,
            'position' => 2, // Ensure unique position
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/sales-opportunities/{$opportunity->id}/move", [
            'pipeline_stage_id' => $newStage->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'pipeline_stage_id',
                ]
            ]);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'pipeline_stage_id' => $newStage->id,
        ]);
    }

    public function test_convert_returns_unauthorized_if_user_is_not_authenticated()
    {
        $opportunity = SalesOpportunity::factory()->create([
            'customer_id' => $this->customer->id,
            'sales_pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $response = $this->postJson("/api/v1/sales-opportunities/{$opportunity->id}/convert");

        $response->assertUnauthorized();
    }

    public function test_convert_sales_opportunity_to_sales_order()
    {
        // Use the opportunity created in setUp, which already has status 'won'
        $opportunity = $this->opportunity;

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/sales-opportunities/{$opportunity->id}/convert");

        $response->assertOk() // Changed to assertOk as per the controller's response
            ->assertJson([
                'message' => 'Opportunity successfully converted to Sales Order.',
                'type' => 'success',
                // 'sales_id' => $opportunity->sales_id, // sales_id will be dynamic
            ]);

        $this->assertDatabaseHas('sales', [
            'sales_opportunity_id' => $opportunity->id,
            'total' => $opportunity->amount,
        ]);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'status' => 'converted', // Status will be 'converted'
        ]);
    }
}