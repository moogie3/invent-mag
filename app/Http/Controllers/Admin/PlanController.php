<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlanService;
use Illuminate\Http\Request;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class PlanController extends Controller
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Show the current plan details and usage statistics.
     * Displayed as a settings sub-page (col-3 menu + col-9 content).
     */
    public function index(Request $request)
    {
        try {
            // Try to get fresh tenant from database
            $tenant = app('currentTenant');
            $tenant->refresh();
        } catch (\Exception $e) {
            // If tenant binding doesn't exist, continue without it
            $tenant = null;
        }
        
        $usageStats = $this->planService->getUsageStats();
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');

        // Handle success redirect from checkout
        if ($request->query('success') == 1 && $tenant) {
            // Check for recent paid order
            $recentOrder = \App\Models\SubscriptionOrder::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->latest('paid_at')
                ->first();

            if ($recentOrder && $recentOrder->paid_at > now()->subMinutes(5)) {
                // Refresh the plan data after upgrade
                $tenant->refresh();
                $currentPlan = $tenant->getEffectivePlan();
                $usageStats = $this->planService->getUsageStats();
                
                // Clear the query string to prevent re-submission
                return redirect()->route('admin.setting.plan')->with('success', 'Payment successful! You are now on the ' . $currentPlan->name . ' plan.');
            }
        }

        return view('admin.plan.index', compact('usageStats', 'currentPlan', 'plans', 'features'));
    }

    /**
     * Show the upgrade page with plan comparison.
     */
    public function upgrade(Request $request)
    {
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');
        $feature = $request->query('feature');
        $suggestedPlan = $feature ? $this->planService->getSuggestedUpgrade($feature) : null;
        $upgradeMessage = $feature ? (config("plans.upgrade_messages.{$feature}") ?? null) : null;

        return view('admin.plan.upgrade', compact(
            'currentPlan',
            'plans',
            'features',
            'feature',
            'suggestedPlan',
            'upgradeMessage'
        ));
    }

    /**
     * Change the tenant's plan.
     */
    public function change(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,slug',
        ]);

        $tenant = app('currentTenant');
        $newPlan = \App\Models\Plan::where('slug', $request->input('plan'))->first();

        // 1. Create a Pending Subscription Order
        $orderNumber = 'INV-' . strtoupper(uniqid()) . '-' . time();
        
        // Convert USD to IDR for Midtrans (minimum 10,000 IDR required)
        $exchangeRate = 16000;
        $priceIdr = (int) round($newPlan->price * $exchangeRate);
        
        $order = \App\Models\SubscriptionOrder::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $newPlan->id,
            'order_number' => $orderNumber,
            'amount' => $priceIdr,
            'status' => 'pending',
        ]);

        // 2. Configure Midtrans
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        // 3. Build Midtrans Payload
        // Use $priceIdr which is already calculated above
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $priceIdr,
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'company' => $tenant->name,
            ],
            'item_details' => [
                [
                    'id' => $newPlan->slug,
                    'price' => $priceIdr,
                    'quantity' => 1,
                    'name' => $newPlan->name . ' Subscription',
                ]
            ]
        ];

        try {
            // 4. Get Snap Token
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            
            $order->update(['snap_token' => $snapToken]);

            // 5. Return view with Snap Token to render popup
            $currentPlan = $tenant->plan;

            // Turbo Drive handling - return redirect response
            if ($request->header('X-Laravel-JSON') || $request->expectsJson()) {
                return response()->json([
                    'redirect' => route('admin.setting.plan.checkout', ['order' => $order->id])
                ]);
            }

            // For Turbo Drive form submissions, return redirect (Turbo will follow)
            return redirect()->route('admin.setting.plan.checkout', ['order' => $order->id]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Midtrans Error: ' . $e->getMessage());
            
            if ($request->header('X-Laravel-JSON') || $request->expectsJson()) {
                return response()->json(['error' => 'Payment gateway error. Please try again later.'], 500);
            }
            
            return back()->with('error', 'Payment gateway error. Please try again later. ' . $e->getMessage());
        }
    }

    /**
     * Show the checkout page with Midtrans Snap token.
     */
    public function checkout(Request $request, $order)
    {
        $order = \App\Models\SubscriptionOrder::findOrFail($order);
        $newPlan = $order->plan;
        $currentPlan = app('currentTenant')->plan;
        $snapToken = $order->snap_token;
        $priceIdr = $order->amount;

        return view('admin.plan.checkout', compact('order', 'snapToken', 'newPlan', 'currentPlan', 'priceIdr'));
    }

    /**
     * Confirm payment from frontend after successful Midtrans transaction.
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'transaction_result' => 'required|array',
        ]);

        $orderId = $request->input('order_id');
        $transactionResult = $request->input('transaction_result');

        // Get current tenant
        $tenant = app('currentTenant');
        
        // Find order and verify it belongs to current tenant
        $order = \App\Models\SubscriptionOrder::where('order_number', $orderId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$order) {
            \Illuminate\Support\Facades\Log::warning("Payment confirm: Order not found or not owned by tenant. Order: {$orderId}, Tenant: {$tenant->id}");
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Check if already processed
        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already processed', 'plan' => $order->plan->name]);
        }

        // Verify the transaction status with Midtrans API
        try {
            \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            
            $status = \Midtrans\Transaction::status($orderId);
            
            // Only process if payment is successful
            if ($status->transaction_status === 'settlement' || $status->transaction_status === 'capture') {
                // Update order status
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_info' => (array) $status
                ]);

                // Update tenant's plan
                $newPlan = $order->plan;
                $tenant->plan_id = $newPlan->id;
                $tenant->plan_status = 'active';
                $tenant->plan_changed_at = now();
                $tenant->save();

                \Illuminate\Support\Facades\Log::info("Payment confirmed for order {$order->order_number}. Tenant {$tenant->name} changed to {$newPlan->name}.");
                
                return response()->json([
                    'message' => 'Payment confirmed successfully',
                    'plan' => $newPlan->name
                ]);
            } else {
                return response()->json([
                    'message' => 'Payment not completed',
                    'status' => $status->transaction_status
                ], 400);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment confirmation error: ' . $e->getMessage());
            
            return response()->json(['message' => 'Payment verification failed. Please try again later.'], 500);
        }
    }
}
