<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Handle an incoming request.
     *
     * Checks whether the current tenant has reached a resource limit.
     * Usage in routes: ->middleware('plan.limit:users')
     *                  ->middleware('plan.limit:warehouses')
     *
     * This middleware only blocks creation (POST/store) requests.
     * View/edit/delete operations are always allowed regardless of limits.
     *
     * @param  string  $resource  The resource type to check ('users' or 'warehouses')
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        // Only enforce limits on creation requests
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        // Skip check if no tenant context
        try {
            $tenant = app('currentTenant');
        } catch (\Exception $e) {
            return $next($request);
        }

        if ($tenant === null) {
            return $next($request);
        }

        // Backward compatibility: tenant has no plan assigned
        if (! $tenant->hasPlan() && config('plans.legacy_full_access', true)) {
            return $next($request);
        }

        $limitReached = false;
        $message = '';

        switch ($resource) {
            case 'users':
                if (! $this->planService->canAddUser()) {
                    $limit = $this->planService->getUserLimit();
                    $limitReached = true;
                    $message = "You have reached the maximum number of users ({$limit}) allowed on your current plan. Please upgrade to add more users.";
                }
                break;

            case 'warehouses':
                if (! $this->planService->canAddWarehouse()) {
                    $limit = $this->planService->getWarehouseLimit();
                    $limitReached = true;
                    $message = "You have reached the maximum number of warehouses ({$limit}) allowed on your current plan. Please upgrade to add more warehouses.";
                }
                break;

            default:
                // Unknown resource type, allow through
                return $next($request);
        }

        if ($limitReached) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => 'plan_limit_reached',
                    'resource' => $resource,
                    'limit' => $resource === 'users'
                        ? $this->planService->getUserLimit()
                        : $this->planService->getWarehouseLimit(),
                ], 403);
            }

            return redirect()->back()
                ->with('error', $message)
                ->withInput();
        }

        return $next($request);
    }
}
