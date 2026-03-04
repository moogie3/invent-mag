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
        $usageStats = $this->planService->getUsageStats();
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');

        // Handle success redirect from checkout
        if ($request->query('success') == 1) {
            // Check for recent paid order
            $tenant = app('currentTenant');
            $recentOrder = \App\Models\SubscriptionOrder::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->latest('paid_at')
                ->first();

            if ($recentOrder && $recentOrder->paid_at > now()->subMinutes(5)) {
                // Refresh the plan data after upgrade
                $currentPlan = $this->planService->getCurrentPlan();
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
            return view('admin.plan.checkout', compact('order', 'snapToken', 'newPlan', 'currentPlan', 'priceIdr'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Midtrans Error: ' . $e->getMessage());
            return back()->with('error', 'Payment gateway error. Please try again later. ' . $e->getMessage());
        }
    }
}
