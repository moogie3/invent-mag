<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\Purchase;
use App\Models\User;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // customize the login view
        View::composer('*', function ($view) {
            $purchaseOrders = Purchase::where('due_date', '<=', now()->addDays(7))
                                        ->where('status', '!=', 'Paid')
                                        ->get();

            $view->with('purchaseOrders', $purchaseOrders);
        });
    }
}