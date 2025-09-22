<?php

namespace App\Providers;

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
        View::composer('admin.layouts.navbar', function ($view) {
            $menu = config('navigation.menu');
            $filteredMenu = array_filter($menu, function ($item) {
                return !isset($item['children']);
            });
            $view->with('navigationItems', $filteredMenu);
        });
    }
}