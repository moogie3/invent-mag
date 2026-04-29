<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\SubscriptionOrder;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class MidtransController extends Controller
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
        \Midtrans\Config::$isProduction = config('midtrans.is_production', false);
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Midtrans Webhook Received', $payload);

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Verify Signature Key
        $serverKey = config('midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $calculatedSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        if ($calculatedSignature !== $signatureKey) {
            Log::error('Midtrans Webhook: Invalid signature', [
                'expected' => $calculatedSignature,
                'received' => $signatureKey
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = SubscriptionOrder::where('order_number', $orderId)->first();

        if (!$order) {
            Log::error("Midtrans Webhook: Order not found ({$orderId})");
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status === 'paid') {
            Log::info("Order {$orderId} already processed, skipping webhook.");
            return response()->json(['message' => 'Order already processed']);
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            // Check if this is a new payment (not already processed by PaymentController)
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_info' => $payload
            ]);

            // Upgrade the tenant's plan (only if not already done via frontend)
            $tenant = $order->tenant;
            $newPlan = $order->plan;
            
            // Explicitly set tenant context for database operations
            $tenant->makeCurrent();

            $currentPlan = $tenant->getCurrentPlan();
            $billingService = app(\App\Services\BillingService::class);
            $changeType = $order->change_type ?? $billingService->getPlanChangeType($currentPlan, $newPlan);

            if (!$tenant->billing_period_start) {
                $period = $billingService->getCurrentBillingPeriod();
                $tenant->billing_period_start = $period['start'];
                $tenant->billing_period_end = $period['end'];
            }

            if ($changeType === 'upgrade') {
                $tenant->plan_id = $newPlan->id;
                $tenant->plan_status = 'active';
                $tenant->trial_ends_at = null;
                $tenant->plan_changed_at = now();
                $tenant->save();
                Log::info("Order {$orderId} marked as paid. Tenant {$tenant->name} upgraded to {$newPlan->name}.");
            } elseif ($changeType === 'same') {
                $tenant->plan_status = 'active';
                $tenant->trial_ends_at = null;
                $tenant->plan_changed_at = now();
                
                if ($tenant->billing_period_end && \Carbon\Carbon::parse($tenant->billing_period_end)->isFuture()) {
                    $tenant->billing_period_end = \Carbon\Carbon::parse($tenant->billing_period_end)->addDays(30);
                } else {
                    $period = $billingService->getCurrentBillingPeriod();
                    $tenant->billing_period_start = $period['start'];
                    $tenant->billing_period_end = $period['end'];
                }
                
                $tenant->save();
                Log::info("Order {$orderId} marked as paid. Tenant {$tenant->name} renewed {$newPlan->name}.");
            } else {
                $nextPeriod = $billingService->getNextBillingPeriod();
                $daysRemaining = $billingService->getDaysRemainingInPeriod($tenant);
                $creditAmount = $billingService->calculateDowngradeCredit($currentPlan, $newPlan, $daysRemaining);
                
                $tenant->pending_plan_id = $newPlan->id;
                $tenant->pending_plan_effective_at = $nextPeriod['start'] . ' 00:00:00';
                $tenant->proration_credit = $creditAmount;
                $tenant->trial_ends_at = null;
                $tenant->save();
                Log::info("Order {$orderId} marked as paid. Tenant {$tenant->name} scheduled downgrade to {$newPlan->name}.");
            }

            // Send invoice email to the first user (usually the owner/admin)
            $order->refresh();
            $recipientEmail = \App\Models\User::first()?->email;
            if ($recipientEmail) {
                Mail::to($recipientEmail)->send(new SubscriptionInvoiceMail($order));
                Log::info("Invoice email sent to {$recipientEmail} for order {$orderId}.");
            }

            \Spatie\Multitenancy\Models\Tenant::forgetCurrent();
            
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $order->update([
                'status' => 'failed',
                'payment_info' => $payload
            ]);
            Log::info("Order {$orderId} failed or expired.");
        }

        return response()->json(['message' => 'Webhook handled successfully']);
    }
}
