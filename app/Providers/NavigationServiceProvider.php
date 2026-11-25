<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['admin.layouts.navbar', 'admin.layouts.menu-sidebar'], function ($view) {
            $menu = $this->filterMenu(config('navigation.menu'));
            $navigationType = auth()->user()->system_settings['navigation_type'] ?? 'sidebar';
            $view->with('navigationItems', $menu)
                ->with('navigationType', $navigationType);
        });
    }

    /**
     * Filter navigation by tax jurisdiction only (no plan gating).
     */
    protected function filterMenu(array $menu): array
    {
        $tenant = $this->getCurrentTenant();

        return array_values(array_filter(
            array_map(function (array $item) use ($tenant) {
                if (isset($item['jurisdiction']) && ! $this->tenantMatchesJurisdiction($tenant, $item['jurisdiction'])) {
                    return null;
                }

                if (isset($item['children'])) {
                    $item['children'] = array_values(array_filter(
                        $item['children'],
                        function (array $child) use ($tenant) {
                            if (isset($child['jurisdiction']) && ! $this->tenantMatchesJurisdiction($tenant, $child['jurisdiction'])) {
                                return false;
                            }

                            return true;
                        }
                    ));

                    if (empty($item['children'])) {
                        return null;
                    }
                }

                return $item;
            }, $menu),
            fn ($item) => $item !== null
        ));
    }

    protected function getCurrentTenant(): ?\App\Models\Tenant
    {
        try {
            $tenant = app('currentTenant');

            return $tenant instanceof \App\Models\Tenant ? $tenant : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function tenantMatchesJurisdiction(?\App\Models\Tenant $tenant, string $jurisdiction): bool
    {
        if ($tenant === null) {
            return true;
        }

        return $tenant->tax_jurisdiction === $jurisdiction;
    }
}
