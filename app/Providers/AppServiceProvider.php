<?php

namespace App\Providers;

use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Purchase;
use Illuminate\Support\Facades\URL;

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
        if (! app()->environment('testing')) {
            View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
                $notificationService = app(NotificationService::class);
                $notifications = $notificationService->getDueNotifications();
                $notificationCount = $notifications->count();

                $view->with('notificationCount', $notificationCount);
                $view->with('notifications', $notifications);
            });
        }

        // customize the login view
        if (! app()->environment('testing')) {
            View::composer(['admin.purchase.*', 'admin.dashboard'], function ($view) {
                $purchaseOrders = Purchase::where('due_date', '<=', now()->addDays(7))
                    ->where('status', '!=', 'Paid')
                    ->get();

                $view->with('purchaseOrders', $purchaseOrders);
            });
        }

        if (app()->environment('production')) {
        URL::forceScheme('https');
        }
    }
}
