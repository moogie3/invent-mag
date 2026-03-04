<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\SubscriptionOrder;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class PaymentController extends Controller
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
        \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
    }

    /**
     * Confirm payment directly from frontend after successful Snap transaction.
     * This ensures the plan is updated immediately without waiting for webhook.
     * 
     * Security: Verifies the order belongs to the current tenant.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'transaction_result' => 'required|array',
        ]);

        $orderId = $request->input('order_id');
        $transactionResult = $request->input('transaction_result');

        // Get current tenant
        $currentTenant = app('currentTenant');
        
        // Find order and verify it belongs to current tenant
        $order = SubscriptionOrder::where('order_number', $orderId)
            ->where('tenant_id', $currentTenant->id)
            ->first();

        if (!$order) {
            Log::warning("Payment confirm: Order not found or not owned by tenant. Order: {$orderId}, Tenant: {$currentTenant->id}");
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Check if already processed
        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already processed', 'plan' => $order->plan->name]);
        }

        // Verify the transaction status with Midtrans API
        try {
            $status = \Midtrans\Transaction::status($orderId);
            
            // Only process if payment is successful
            if ($status->transaction_status === 'settlement' || $status->transaction_status === 'capture') {
                $this->processSuccessfulPayment($order, (array) $status);
                
                return response()->json([
                    'message' => 'Payment confirmed successfully',
                    'plan' => $order->plan->name
                ]);
            } else {
                return response()->json([
                    'message' => 'Payment not completed',
                    'status' => $status->transaction_status
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment confirmation error: ' . $e->getMessage());
            
            // If we can't verify with Midtrans, check if the frontend result indicates success
            if (isset($transactionResult['transaction_status']) && 
                ($transactionResult['transaction_status'] === 'settlement' || $transactionResult['transaction_status'] === 'capture')) {
                
                $this->processSuccessfulPayment($order, $transactionResult);

                return response()->json([
                    'message' => 'Payment confirmed (offline verification)',
                    'plan' => $order->plan->name
                ]);
            }
            
            return response()->json(['message' => 'Payment verification failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process successful payment - shared logic to avoid duplication.
     */
    private function processSuccessfulPayment(SubscriptionOrder $order, array $paymentInfo): void
    {
        // Double-check status to prevent race conditions
        if ($order->status === 'paid') {
            return;
        }

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_info' => $paymentInfo
        ]);

        // Upgrade the tenant's plan
        $tenant = $order->tenant;
        $plan = $order->plan;
        
        // False for trial because they actually paid
        $this->planService->assignPlanToTenant($tenant, $plan->slug, false);

        // Send invoice email
        $order->refresh();
        $recipientEmail = $tenant->users()->first()?->email;
        if ($recipientEmail) {
            Mail::to($recipientEmail)->send(new SubscriptionInvoiceMail($order));
        }

        Log::info("Payment confirmed for order {$order->order_number}. Tenant {$tenant->name} upgraded to {$plan->name}.");
    }
}
