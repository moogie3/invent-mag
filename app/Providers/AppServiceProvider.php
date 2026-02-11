<?php

namespace App\Providers;

use App\Helpers\CurrencyHelper;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Purchase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Multitenancy;

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
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $tenantDomain = $notifiable->tenant->domain;
            $isSecure = $this->app->environment('production');
            $schema = $isSecure ? 'https' : 'http';
            $rootUrl = "{$schema}://{$tenantDomain}";

            // Temporarily force the root URL to the tenant's domain for signing
            URL::forceRootUrl($rootUrl);

            $url = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // Revert the URL generator to its default state
            URL::forceRootUrl(null);

            return $url;
        });

        if (! app()->environment('testing')) {
            View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
                if (app()->has('currentTenant')) { // Check if a tenant is active
                    $notificationService = app(NotificationService::class);
                    $notifications = $notificationService->getDueNotifications();
                    $notificationCount = $notifications->count();

                    $view->with('notificationCount', $notificationCount);
                    $view->with('notifications', $notifications);
                } else {
                    // Provide empty defaults for pages without a tenant (like the central login page)
                    $view->with('notificationCount', 0);
                    $view->with('notifications', collect());
                }
            });
        }

        // customize the login view
        if (app()->environment('testing')) {
            // Do nothing for testing environment
        } else {
            View::composer(['admin.purchase.*', 'admin.dashboard'], function ($view) {
                $purchaseOrders = Purchase::where('due_date', '<=', now()->addDays(7))
                    ->where('status', '!=', 'Paid')
                    ->get();

                $view->with('purchaseOrders', $purchaseOrders);
            });
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60
                    ], 429);
                });
        });
    }
}