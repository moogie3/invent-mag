<?php

namespace App\Providers;

use App\Helpers\CurrencyHelper;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\SystemNotificationService;
use App\Observers\ActivityObserver;
use App\Observers\CustomerCreditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
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
        // Register CustomerCredit observer
        \App\Models\CustomerCredit::observe(CustomerCreditObserver::class);

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
         * DB::tenantTable() — Safeguard against missing tenant_id in raw queries.
         *
         * Auto-injects ->where('tenant_id', $tenantId) so developers cannot forget it.
         * Pass $requireTenant = false to opt out (e.g. cross-tenant admin queries).
         */
        DB::macro('tenantTable', function (string $table, ?int $tenantId = null, bool $requireTenant = true) {
            $tenantId = $tenantId ?? app('currentTenant')->id ?? null;
            $query = DB::table($table);
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            } elseif ($requireTenant) {
                throw new \RuntimeException(
                    'DB::tenantTable() called without a tenant context. ' .
                    'Pass $tenantId explicitly or set $requireTenant = false if this is intentional.'
                );
            }
            return $query;
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

        // ---------------------------------------------------------------
        // Activity Log Observers — register for all auditable models
        // ---------------------------------------------------------------
        $this->registerActivityObservers();

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

    /**
     * Register Eloquent model observers for the activity audit log.
     * Each observer is a lightweight wrapper around ActivityLogService.
     */
    protected function registerActivityObservers(): void
    {
        // Skip observers in console/queue context where there's no auth user
        // (seeders, migrations, scheduled jobs). They still fire but the service
        // handles null users gracefully.

        $map = [
            \App\Models\Product::class          => \App\Models\ActivityLog::MODULE_PRODUCT,
            \App\Models\Sales::class            => \App\Models\ActivityLog::MODULE_SALES,
            \App\Models\Purchase::class         => \App\Models\ActivityLog::MODULE_PURCHASE_ORDER,
            \App\Models\SalesReturn::class      => \App\Models\ActivityLog::MODULE_SALES_RETURN,
            \App\Models\PurchaseReturn::class   => \App\Models\ActivityLog::MODULE_PURCHASE_RETURN,
            \App\Models\Customer::class         => \App\Models\ActivityLog::MODULE_CUSTOMER,
            \App\Models\Supplier::class         => \App\Models\ActivityLog::MODULE_SUPPLIER,
            \App\Models\Warehouse::class        => \App\Models\ActivityLog::MODULE_WAREHOUSE,
            \App\Models\Categories::class       => \App\Models\ActivityLog::MODULE_CATEGORY,
            \App\Models\Unit::class             => \App\Models\ActivityLog::MODULE_UNIT,
            \App\Models\Tax::class              => \App\Models\ActivityLog::MODULE_TAX,
            \App\Models\CurrencySetting::class  => \App\Models\ActivityLog::MODULE_CURRENCY,
            \App\Models\User::class             => \App\Models\ActivityLog::MODULE_USER,
            \App\Models\JournalEntry::class     => \App\Models\ActivityLog::MODULE_JOURNAL_ENTRY,
            \App\Models\StockAdjustment::class  => \App\Models\ActivityLog::MODULE_STOCK_ADJUSTMENT,
            \App\Models\Payment::class          => \App\Models\ActivityLog::MODULE_PAYMENT,
        ];

        $logService = $this->app->make(ActivityLogService::class);

        foreach ($map as $modelClass => $module) {
            $observer = new ActivityObserver($logService);
            $observer->setModule($module);
            $modelClass::observe($observer);
        }
    }
}