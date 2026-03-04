<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\SubscriptionOrder;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that webhook rejects invalid payload.
     */
    public function test_webhook_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => 'INV-123',
            // Missing required fields
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test that webhook rejects invalid signature.
     */
    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['midtrans.server_key' => 'test_server_key']);

        $payload = [
            'order_id' => 'INV-TEST-001',
            'status_code' => '200',
            'gross_amount' => '49000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid_signature'
        ];

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test that webhook rejects order not found.
     */
    public function test_webhook_rejects_order_not_found(): void
    {
        $serverKey = 'test_server_key';
        $orderId = 'INV-NONEXISTENT-001';
        $statusCode = '200';
        $grossAmount = '49000.00';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => $signature
        ];

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertStatus(404);
    }

    /**
     * Test successful payment processing.
     */
    public function test_webhook_processes_successful_payment(): void
    {
        // Create tenant and plan
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $plan = Plan::create([
            'slug' => 'professional',
            'name' => 'Professional',
            'price' => 49000,
            'is_active' => true,
        ]);

        // Create pending order
        $order = SubscriptionOrder::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'order_number' => 'INV-TEST-SUCCESS',
            'amount' => 49000,
            'status' => 'pending',
        ]);

        // Generate valid signature
        $serverKey = 'test_server_key';
        $orderId = 'INV-TEST-SUCCESS';
        $statusCode = '200';
        $grossAmount = '49000.00';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => $signature,
            'payment_type' => 'credit_card',
        ];

        // Mock config for the test
        config(['midtrans.server_key' => $serverKey]);

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertStatus(200);

        // Verify order was updated
        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->paid_at);

        // Verify tenant plan was upgraded
        $tenant->refresh();
        $this->assertEquals($plan->id, $tenant->plan_id);
    }

    /**
     * Test that duplicate webhook doesn't double upgrade.
     */
    public function test_webhook_ignores_already_processed_order(): void
    {
        // Create tenant and plan
        $tenant = Tenant::create([
            'name' => 'Test Tenant 2',
            'domain' => 'test2.example.com',
        ]);

        $plan = Plan::create([
            'slug' => 'enterprise',
            'name' => 'Enterprise',
            'price' => 89000,
            'is_active' => true,
        ]);

        // Create already paid order
        $order = SubscriptionOrder::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'order_number' => 'INV-TEST-DUPLICATE',
            'amount' => 89000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Generate valid signature
        $serverKey = 'test_server_key';
        $orderId = 'INV-TEST-DUPLICATE';
        $statusCode = '200';
        $grossAmount = '89000.00';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => $signature,
        ];

        config(['midtrans.server_key' => $serverKey]);

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Order already processed']);
    }

    /**
     * Test failed payment processing.
     */
    public function test_webhook_processes_failed_payment(): void
    {
        // Create tenant and plan
        $tenant = Tenant::create([
            'name' => 'Test Tenant 3',
            'domain' => 'test3.example.com',
        ]);

        $plan = Plan::create([
            'slug' => 'professional',
            'name' => 'Professional',
            'price' => 49000,
            'is_active' => true,
        ]);

        // Create pending order
        $order = SubscriptionOrder::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'order_number' => 'INV-TEST-FAILED',
            'amount' => 49000,
            'status' => 'pending',
        ]);

        // Generate valid signature
        $serverKey = 'test_server_key';
        $orderId = 'INV-TEST-FAILED';
        $statusCode = '202';
        $grossAmount = '49000.00';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'expire',
            'signature_key' => $signature,
        ];

        config(['midtrans.server_key' => $serverKey]);

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertStatus(200);

        // Verify order was marked as failed
        $order->refresh();
        $this->assertEquals('failed', $order->status);
        $this->assertNull($order->paid_at);

        // Verify tenant plan was NOT upgraded
        $tenant->refresh();
        $this->assertNull($tenant->plan_id);
    }
}
