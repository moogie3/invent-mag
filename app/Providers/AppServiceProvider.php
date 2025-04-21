<?php

namespace App\Providers;

use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Purchase;

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
        View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
            $notificationController = app(NotificationController::class);
            $notifications = $notificationController->getDueNotifications();
            $notificationCount = $notifications->count();

            $view->with('notificationCount', $notificationCount);
            $view->with('notifications', $notifications);
        });

        // customize the login view
        View::composer(['admin.purchase.*', 'admin.dashboard'], function ($view) {
            $purchaseOrders = Purchase::where('due_date', '<=', now()->addDays(7))
                ->where('status', '!=', 'Paid')
                ->get();

            $view->with('purchaseOrders', $purchaseOrders);
        });
    }
}