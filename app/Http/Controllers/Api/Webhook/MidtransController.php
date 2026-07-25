<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\SubscriptionOrder;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class MidtransController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
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
            $tenant = $order->tenant;
            $tenant->makeCurrent();

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_info' => $payload,
            ]);

            $this->billingService->confirmPayment($order, $payload);

            // Send invoice email
            $order->refresh();
            $recipientEmail = $tenant->users()->first()?->email;
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
