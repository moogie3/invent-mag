<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Get active subscription plans (public pricing data).
     *
     * Returns the active plans used by the marketing-site pricing cards
     * (`invent-mag-fe/src/lib/plans.ts` fetches this at build time). Only
     * display-oriented fields are exposed — no relations or internals.
     *
     * @group Plans
     * Subscription plan pricing (public) and authenticated plan/usage data.
     *
     * @unauthenticated
     *
     * @response scenario=success {
     *   "data": [
     *     {
     *       "slug": "starter",
     *       "name": "Starter",
     *       "description": "Everything you need to manage inventory and orders.",
     *       "price": 19,
     *       "currency_code": "USD",
     *       "billing_cycle": "monthly",
     *       "trial_days": 30,
     *       "max_users": 3,
     *       "max_warehouses": 1,
     *       "max_products": 100,
     *       "features": []
     *     }
     *   ],
     *   "meta": {
     *     "currency_code": "USD"
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        $plans = Plan::active()
            ->ordered()
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'currency_code' => $plan->currency_code,
                    'billing_cycle' => $plan->billing_cycle,
                    'trial_days' => $plan->trial_days,
                    'max_users' => $plan->max_users,
                    'max_warehouses' => $plan->max_warehouses,
                    'max_products' => $plan->max_products,
                    'features' => $plan->features,
                ];
            });

        return response()->json([
            'data' => $plans,
            'meta' => [
                'currency_code' => config('billing.currency', 'USD'),
            ],
        ])->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * List active plans (authenticated).
     *
     * Returns the full active plan records for an authenticated client.
     *
     * @group Plans
     * Subscription plan pricing (public) and authenticated plan/usage data.
     *
     * @response scenario=success [
     *   {
     *     "slug": "starter",
     *     "name": "Starter",
     *     "price": "19.00",
     *     "currency_code": "USD",
     *     "billing_cycle": "monthly",
     *     "trial_days": 30,
     *     "max_users": 3,
     *     "max_warehouses": 1,
     *     "max_products": 100,
     *     "features": [],
     *     "is_active": true,
     *     "sort_order": 1
     *   }
     * ]
     */
    public function authenticatedIndex(): JsonResponse
    {
        return response()->json(Plan::active()->ordered()->get());
    }

    /**
     * Get the current tenant's plan usage statistics.
     *
     * Returns the authenticated tenant's effective plan along with current
     * usage against plan limits (users, warehouses, products) and trial status.
     *
     * @group Plans
     * Subscription plan pricing (public) and authenticated plan/usage data.
     *
     * @response scenario=success {
     *   "plan": {
     *     "slug": "professional",
     *     "name": "Professional",
     *     "price": "59.00",
     *     "max_users": 10,
     *     "max_warehouses": -1,
     *     "max_products": 500
     *   },
     *   "tenant": {
     *     "name": "My Company",
     *     "domain": "mycompany.invent-mag.web.id"
     *   },
     *   "users": {
     *     "current": 3,
     *     "limit": 10,
     *     "unlimited": false,
     *     "percentage": 30
     *   },
     *   "warehouses": {
     *     "current": 2,
     *     "limit": -1,
     *     "unlimited": true,
     *     "percentage": 0
     *   },
     *   "products": {
     *     "current": 150,
     *     "limit": 500,
     *     "unlimited": false,
     *     "percentage": 30
     *   },
     *   "trial": {
     *     "on_trial": true,
     *     "days_remaining": 18
     *   }
     * }
     */
    public function current(Request $request): JsonResponse
    {
        $planService = app(PlanService::class);

        return response()->json($planService->getUsageStats());
    }
}
