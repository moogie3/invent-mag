<?php

namespace App\Providers;

use App\Helpers\CurrencyHelper;
use App\Services\NotificationService;
use App\Services\SystemNotificationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
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
            
            // Temporarily force the root URL to the tenant subdomain for signing
            URL::forceRootUrl("https://{$tenantDomain}");

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

        // Custom Reset Password URL using tenant subdomain
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            return "https://{$user->tenant->domain}/admin/reset-password/{$token}?email={$user->email}";
        });

        // // NOTE: Pass the user object to the custom email notification view
        // This fixes the "your email address" bug in the template
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(__('Verify Email Address'))
                ->view('vendor.notifications.email', [
                    'notifiable' => $notifiable,
                    'actionUrl'  => $url,
                    'actionText' => __('Verify Email Address'),
                    'level'      => 'info',
                    'introLines' => [__('Please click the button below to verify your email address.')],
                    'outroLines' => [__('If you did not create an account, no further action is required.')]
                ]);
        });

        // ---------------------------------------------------------------
        // Custom Blade Directives for Plan System
        // ---------------------------------------------------------------

        /**
         * @planHas('feature_slug') ... @endPlanHas
         * Conditionally renders content if the current tenant's plan includes the feature.
         * Returns true (show content) if: no tenant context, legacy tenant, or plan has feature.
         */
        Blade::if('planHas', function (string $feature) {
            try {
                $tenant = app('currentTenant');
                return $tenant instanceof \App\Models\Tenant ? $tenant->hasFeature($feature) : true;
            } catch (\Exception $e) {
                return true; // No tenant context, allow content
            }
        });

        /**
         * @onTrial ... @endOnTrial
         * Conditionally renders content if the current tenant is on a trial period.
         */
        Blade::if('onTrial', function () {
            try {
                $tenant = app('currentTenant');
                return $tenant instanceof \App\Models\Tenant && $tenant->onTrial();
            } catch (\Exception $e) {
                return false;
            }
        });

        if (! app()->environment('testing')) {
            View::composer(['admin.*'], function ($view) {
                // ... (Existing notification logic) ...

                // Add container class logic
                if (auth()->check()) {
                    $fluidLayout = auth()->user()->system_settings['fluid_layout'] ?? false;
                    $view->with('containerClass', $fluidLayout ? 'container-fluid' : 'container-xl');
                } else {
                     // Default for guests (e.g., login page) - usually narrow, but let's stick to standard
                    $view->with('containerClass', 'container-xl');
                }
            });
            
            View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
                if (app()->has('currentTenant')) { // Check if a tenant is active
                    $notificationService = app(NotificationService::class);
                    $notifications = $notificationService->getDueNotifications();
                    $notificationCount = $notifications->count();

                    $view->with('notificationCount', $notificationCount);
                    $view->with('notifications', $notifications);

                    // System notifications (trial, plan expiry, usage limits, setup)
                    $systemNotificationService = app(SystemNotificationService::class);
                    $systemNotifications = $systemNotificationService->getSystemNotifications();
                    $systemNotificationCount = $systemNotifications->count();

                    $view->with('systemNotifications', $systemNotifications);
                    $view->with('systemNotificationCount', $systemNotificationCount);
                    $view->with('totalNotificationCount', $notificationCount + $systemNotificationCount);
                } else {
                    // Provide empty defaults for pages without a tenant (like the central login page)
                    $view->with('notificationCount', 0);
                    $view->with('notifications', collect());
                    $view->with('systemNotifications', collect());
                    $view->with('systemNotificationCount', 0);
                    $view->with('totalNotificationCount', 0);
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