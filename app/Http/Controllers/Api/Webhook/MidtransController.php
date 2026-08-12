<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\SubscriptionOrder;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config;
use Spatie\Multitenancy\Models\Tenant;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class MidtransController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$serverKey = config('midtrans.server_key');
    }

    /**
     * Handle a Midtrans payment notification webhook.
     *
     * Receives Midtrans transaction notifications, verifies the SHA-512
     * signature, and updates the matching subscription order (paid on
     * `capture`/`settlement`, failed on `cancel`/`deny`/`expire`). Configure
     * this URL in the Midtrans dashboard; `POST /api/webhook` is a fallback
     * alias for misconfigured dashboards.
     *
     * @group Webhooks
     * Midtrans payment notification endpoints.
     *
     * @unauthenticated
     *
     * @bodyParam order_id string required The subscription order number.
     * @bodyParam status_code string required The Midtrans HTTP status code.
     * @bodyParam gross_amount string required The transaction amount.
     * @bodyParam signature_key string required The HMAC-SHA512 signature over `order_id + status_code + gross_amount + server_key`.
     * @bodyParam transaction_status string optional The Midtrans transaction status: capture, settlement, cancel, deny, or expire.
     *
     * @response scenario=success {
     *   "message": "Webhook handled successfully"
     * }
     * @response status=400 scenario="invalid payload" {
     *   "message": "Invalid payload"
     * }
     * @response status=403 scenario="bad signature" {
     *   "message": "Invalid signature"
     * }
     * @response status=404 scenario="order not found" {
     *   "message": "Order not found"
     * }
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Midtrans Webhook Received', $payload);

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Verify Signature Key
        $serverKey = config('midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $calculatedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($calculatedSignature !== $signatureKey) {
            Log::error('Midtrans Webhook: Invalid signature', [
                'expected' => $calculatedSignature,
                'received' => $signatureKey,
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = SubscriptionOrder::where('order_number', $orderId)->first();

        if (! $order) {
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

            Tenant::forgetCurrent();

        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $order->update([
                'status' => 'failed',
                'payment_info' => $payload,
            ]);
            Log::info("Order {$orderId} failed or expired.");
        }

        return response()->json(['message' => 'Webhook handled successfully']);
    }

    /**
     * Handle a Midtrans payment notification webhook (fallback alias).
     *
     * Identical to `POST /api/webhooks/midtrans`, exposed as a fallback alias
     * for Midtrans dashboards that were configured with the bare `/webhook` URL.
     *
     * @group Webhooks
     * Midtrans payment notification endpoints.
     *
     * @unauthenticated
     *
     * @bodyParam order_id string required The subscription order number.
     * @bodyParam status_code string required The Midtrans HTTP status code.
     * @bodyParam gross_amount string required The transaction amount.
     * @bodyParam signature_key string required The HMAC-SHA512 signature over `order_id + status_code + gross_amount + server_key`.
     * @bodyParam transaction_status string optional The Midtrans transaction status: capture, settlement, cancel, deny, or expire.
     *
     * @response scenario=success {
     *   "message": "Webhook handled successfully"
     * }
     * @response status=400 scenario="invalid payload" {
     *   "message": "Invalid payload"
     * }
     * @response status=403 scenario="bad signature" {
     *   "message": "Invalid signature"
     * }
     * @response status=404 scenario="order not found" {
     *   "message": "Order not found"
     * }
     */
    public function handleFallback(Request $request)
    {
        return $this->handle($request);
    }
}
