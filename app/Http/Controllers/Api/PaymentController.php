<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\SubscriptionOrder;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config;
use Midtrans\Transaction;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class PaymentController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$serverKey = config('midtrans.server_key');
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

        $currentTenant = app('currentTenant');

        $order = SubscriptionOrder::where('order_number', $orderId)
            ->where('tenant_id', $currentTenant->id)
            ->first();

        if (! $order) {
            Log::warning("Payment confirm: Order not found or not owned by tenant. Order: {$orderId}, Tenant: {$currentTenant->id}");

            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already processed', 'plan' => $order->plan->name]);
        }

        try {
            $status = Transaction::status($orderId);

            if ($status->transaction_status === 'settlement' || $status->transaction_status === 'capture') {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_info' => (array) $status,
                ]);

                $tenant = $order->tenant;
                $result = $this->billingService->confirmPayment($order, (array) $status);

                // Send invoice email
                $order->refresh();
                $recipientEmail = $tenant->users()->first()?->email;
                if ($recipientEmail) {
                    Mail::to($recipientEmail)->send(new SubscriptionInvoiceMail($order));
                }

                return response()->json([
                    'message' => $result['message'],
                    'plan' => $result['plan'],
                ]);
            } else {
                return response()->json([
                    'message' => 'Payment not completed',
                    'status' => $status->transaction_status,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment confirmation error: '.$e->getMessage());

            return response()->json(['message' => 'Payment verification failed. Please try again later.'], 500);
        }
    }
}
