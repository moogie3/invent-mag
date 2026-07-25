<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer(['admin.layouts.navbar', 'admin.layouts.menu-sidebar'], function ($view) {
            $menu = $this->filterMenuByPlan(config('navigation.menu'));
            $navigationType = auth()->user()->system_settings['navigation_type'] ?? 'sidebar';
            $view->with('navigationItems', $menu)
                 ->with('navigationType', $navigationType);
        });
    }

    /**
     * Filter navigation menu items based on the current tenant's plan.
     *
     * Items with a 'plan_feature' key are only shown if the tenant's plan
     * includes that feature. Items without 'plan_feature' are always shown.
     * Children are also filtered individually.
     */
    protected function filterMenuByPlan(array $menu): array
    {
        $tenant = $this->getCurrentTenant();

        return array_values(array_filter(
            array_map(function (array $item) use ($tenant) {
                // Check parent-level jurisdiction
                if (isset($item['jurisdiction']) && ! $this->tenantMatchesJurisdiction($tenant, $item['jurisdiction'])) {
                    return null;
                }

                // Check parent-level plan_feature
                if (isset($item['plan_feature']) && ! $this->tenantHasFeature($tenant, $item['plan_feature'])) {
                    return null;
                }

                // Filter children by plan_feature and jurisdiction
                if (isset($item['children'])) {
                    $item['children'] = array_values(array_filter(
                        $item['children'],
                        function (array $child) use ($tenant) {
                            if (isset($child['jurisdiction']) && ! $this->tenantMatchesJurisdiction($tenant, $child['jurisdiction'])) {
                                return false;
                            }
                            if (isset($child['plan_feature'])) {
                                return $this->tenantHasFeature($tenant, $child['plan_feature']);
                            }
                            return true;
                        }
                    ));

                    // If all children were filtered out, hide the parent too
                    if (empty($item['children'])) {
                        return null;
                    }
                }

                return $item;
            }, $menu),
            fn ($item) => $item !== null
        ));
    }

    /**
     * Get the current tenant, or null if not in a tenant context.
     */
    protected function getCurrentTenant(): ?Tenant
    {
        try {
            $tenant = app('currentTenant');
            return $tenant instanceof Tenant ? $tenant : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a tenant has a given plan feature.
     * Returns true if no tenant context (central pages) or backward-compat mode.
     */
    protected function tenantHasFeature(?Tenant $tenant, string $feature): bool
    {
        if ($tenant === null) {
            return true; // No tenant context, show everything
        }

        return $tenant->hasFeature($feature);
    }

    /**
     * Check if a tenant matches the required jurisdiction.
     * Returns true if no tenant context or no jurisdiction requirement.
     */
    protected function tenantMatchesJurisdiction(?Tenant $tenant, string $jurisdiction): bool
    {
        if ($tenant === null) {
            return true;
        }

        return $tenant->tax_jurisdiction === $jurisdiction;
    }
}