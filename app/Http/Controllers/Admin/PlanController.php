<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionOrder;
use App\Services\BillingService;
use App\Services\PlanService;
use Illuminate\Http\Request;

// Load Midtrans SDK (Non-namespaced)
require_once base_path('vendor/midtrans/midtrans-php/Midtrans.php');

class PlanController extends Controller
{
    protected PlanService $planService;
    protected BillingService $billingService;

    public function __construct(PlanService $planService, BillingService $billingService)
    {
        $this->planService = $planService;
        $this->billingService = $billingService;
    }

    /**
     * Show the current plan details and usage statistics.
     * Displayed as a settings sub-page (col-3 menu + col-9 content).
     */
    public function index(Request $request)
    {
        try {
            $tenant = app('currentTenant');
            $tenant->refresh();
        } catch (\Exception $e) {
            $tenant = null;
        }

        $usageStats = $this->planService->getUsageStats();
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');

        if ($request->query('success') == 1 && $tenant) {
            $recentOrder = SubscriptionOrder::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->latest('paid_at')
                ->first();

            if ($recentOrder && $recentOrder->paid_at > now()->subMinutes(5)) {
                $tenant->refresh();

                $currentPlan = $tenant->getEffectivePlan();
                $usageStats = $this->planService->getUsageStats();

                $newPlan = $recentOrder->plan;
                $oldPlan = $tenant->getCurrentPlan();

                $message = '';

                if ($tenant->hasScheduledDowngrade()) {
                    $scheduledPlan = $tenant->getScheduledPlan();
                    $effectiveDate = $tenant->pending_plan_effective_at->format('F d, Y');
                    $credit = $tenant->proration_credit ?? 0;

                    $message = "Payment successful! Your downgrade to {$scheduledPlan->name} will take effect on {$effectiveDate}. You will keep your current {$oldPlan->name} plan until then. A credit of $" . number_format($credit, 2) . " will be applied to your next bill.";
                } else {
                    $message = "Payment successful! You are now on the {$currentPlan->name} plan.";
                }

                return redirect()->route('admin.setting.plan')->with('success', $message);
            }
        }

        return view('admin.plan.index', compact('usageStats', 'currentPlan', 'plans', 'features', 'tenant'));
    }

    /**
     * Show the upgrade page with plan comparison.
     */
    public function upgrade(Request $request)
    {
        $tenant = app('currentTenant');
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');
        $feature = $request->query('feature');
        $suggestedPlan = $feature ? $this->planService->getSuggestedUpgrade($feature) : null;
        $upgradeMessage = $feature ? (config("plans.upgrade_messages.{$feature}") ?? null) : null;

        // Check if current plan is expired/inactive (allows renewal of same tier)
        $planInactive = $tenant ? $tenant->planInactive() : false;

        // Detect active trial — used to suppress renewal buttons and expiring-soon banners
        // because a trial countdown should not be conflated with a paid subscription expiry
        $isOnActiveTrial = $tenant ? $tenant->isOnActiveTrial() : false;

         // Check if plan is expiring soon (within 7 days) — only for paid subscriptions.
         // Active trials are excluded because their trial window should not
        // trigger the "renew" workflow.
        $expiringSoon = false;
        $expiryDays = null;
        $currentExpiryDate = null;
        if ($tenant && $currentPlan) {
            $currentExpiryDate = $tenant->getSubscriptionExpiryDate();
            if ($currentExpiryDate) {
                $expiryDays = $tenant->subscriptionDaysRemaining();

                if (!$isOnActiveTrial && $expiryDays >= 0 && $expiryDays <= 7) {
                    $expiringSoon = true;
                }
            }
        }

        return view('admin.plan.upgrade', compact(
            'currentPlan',
            'plans',
            'features',
            'feature',
            'suggestedPlan',
            'upgradeMessage',
            'planInactive',
            'isOnActiveTrial',
            'expiringSoon',
            'expiryDays',
            'currentExpiryDate'
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
        $newPlan = Plan::where('slug', $request->input('plan'))->first();

        $currentPlan = $tenant->getCurrentPlan();
        $changeType = $this->billingService->getPlanChangeType($currentPlan, $newPlan);

        $orderNumber = 'INV-' . strtoupper(uniqid()) . '-' . time();

        $exchangeRate = 16000;
        $taxRate = 0.12;

        if ($changeType === 'upgrade') {
            $daysRemaining = $this->billingService->getDaysRemainingInPeriod($tenant);
            $prorationAmount = $this->billingService->calculateUpgradeProration($currentPlan, $newPlan, $daysRemaining);
            $subtotalIdr = (int) round(($newPlan->price + $prorationAmount) * $exchangeRate);
        } else {
            $subtotalIdr = (int) round($newPlan->price * $exchangeRate);
        }

        $taxIdr = (int) round($subtotalIdr * $taxRate);
        $priceIdr = $subtotalIdr + $taxIdr;

        $order = SubscriptionOrder::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $newPlan->id,
            'order_number' => $orderNumber,
            'amount' => $priceIdr,
            'status' => 'pending',
            'change_type' => $changeType,
        ]);

        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

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
                    'name' => $newPlan->name . ' Subscription' . ($changeType === 'upgrade' ? ' (including prorated upgrade)' : ''),
                ]
            ]
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $order->update(['snap_token' => $snapToken]);

            if ($request->header('X-Laravel-JSON') || $request->expectsJson()) {
                return response()->json([
                    'redirect' => route('admin.setting.plan.checkout', ['order' => $order->id])
                ]);
            }

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
        $order = SubscriptionOrder::findOrFail($order);
        $newPlan = $order->plan;
        $currentPlan = app('currentTenant')->plan;
        $snapToken = $order->snap_token;
        $priceIdr = $order->amount;
        $changeType = $order->change_type;

        $exchangeRate = 16000;
        $priceUsd = $priceIdr / $exchangeRate;
        $taxRate = 0.12;
        $taxAmount = $priceUsd * $taxRate;
        $totalUsd = $priceUsd + $taxAmount;

        return view('admin.plan.checkout', compact('order', 'snapToken', 'newPlan', 'currentPlan', 'priceIdr', 'priceUsd', 'taxAmount', 'totalUsd', 'changeType'));
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

        $tenant = app('currentTenant');

        $order = SubscriptionOrder::where('order_number', $orderId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$order) {
            \Illuminate\Support\Facades\Log::warning("Payment confirm: Order not found or not owned by tenant. Order: {$orderId}, Tenant: {$tenant->id}");
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already processed', 'plan' => $order->plan->name]);
        }

        try {
            \Midtrans\Config::$isProduction = config('midtrans.is_production', false);
            \Midtrans\Config::$serverKey = config('midtrans.server_key');

            $status = \Midtrans\Transaction::status($orderId);

            if ($status->transaction_status === 'settlement' || $status->transaction_status === 'capture') {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_info' => (array) $status,
                ]);

                $result = $this->billingService->confirmPayment($order, (array) $status);

                return response()->json([
                    'message' => $result['message'],
                    'plan' => $result['plan'],
                    'type' => $result['type'],
                    'proration_amount' => $result['proration_amount'] ?? null,
                    'effective_date' => $result['effective_date'] ?? null,
                    'credit_amount' => $result['credit_amount'] ?? null,
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
