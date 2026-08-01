<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Categories;
use App\Models\CurrencySetting;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\CustomerInteraction;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sales;
use App\Models\SalesOpportunity;
use App\Models\SalesPipeline;
use App\Models\SalesReturn;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\SupplierInteraction;
use App\Models\Tax;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\ActivityObserver;
use App\Observers\CustomerCreditObserver;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\SystemNotificationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        CustomerCredit::observe(CustomerCreditObserver::class);

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
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return "https://{$user->tenant->domain}/admin/reset-password/{$token}?email={$user->email}";
        });

        // // NOTE: Pass the user object to the custom email notification view
        // This fixes the "your email address" bug in the template
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject(__('Verify Email Address'))
                ->view('vendor.notifications.email', [
                    'notifiable' => $notifiable,
                    'actionUrl' => $url,
                    'actionText' => __('Verify Email Address'),
                    'level' => 'info',
                    'introLines' => [__('Please click the button below to verify your email address.')],
                    'outroLines' => [__('If you did not create an account, no further action is required.')],
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

                return $tenant instanceof Tenant ? $tenant->hasFeature($feature) : true;
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
                    'DB::tenantTable() called without a tenant context. '.
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

                return $tenant instanceof Tenant && $tenant->onTrial();
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

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
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
            Product::class => ActivityLog::MODULE_PRODUCT,
            Sales::class => ActivityLog::MODULE_SALES,
            Purchase::class => ActivityLog::MODULE_PURCHASE_ORDER,
            SalesReturn::class => ActivityLog::MODULE_SALES_RETURN,
            PurchaseReturn::class => ActivityLog::MODULE_PURCHASE_RETURN,
            Customer::class => ActivityLog::MODULE_CUSTOMER,
            Supplier::class => ActivityLog::MODULE_SUPPLIER,
            Warehouse::class => ActivityLog::MODULE_WAREHOUSE,
            Categories::class => ActivityLog::MODULE_CATEGORY,
            Unit::class => ActivityLog::MODULE_UNIT,
            Tax::class => ActivityLog::MODULE_TAX,
            CurrencySetting::class => ActivityLog::MODULE_CURRENCY,
            User::class => ActivityLog::MODULE_USER,
            JournalEntry::class => ActivityLog::MODULE_JOURNAL_ENTRY,
            StockAdjustment::class => ActivityLog::MODULE_STOCK_ADJUSTMENT,
            StockOpname::class => ActivityLog::MODULE_STOCK_OPNAME,
            Payment::class => ActivityLog::MODULE_PAYMENT,
            Account::class => ActivityLog::MODULE_ACCOUNTING,
            OpeningBalance::class => ActivityLog::MODULE_ACCOUNTING,
            Employee::class => ActivityLog::MODULE_EMPLOYEE,
            AttendanceRecord::class => ActivityLog::MODULE_ATTENDANCE,
            AttendancePeriod::class => ActivityLog::MODULE_ATTENDANCE,
            PayrollRun::class => ActivityLog::MODULE_PAYROLL,
            SalesOpportunity::class => ActivityLog::MODULE_SALES_PIPELINE,
            SalesPipeline::class => ActivityLog::MODULE_SALES_PIPELINE,
            PipelineStage::class => ActivityLog::MODULE_SALES_PIPELINE,
            CustomerInteraction::class => ActivityLog::MODULE_CRM,
            SupplierInteraction::class => ActivityLog::MODULE_SRM,
        ];

        $logService = $this->app->make(ActivityLogService::class);

        foreach ($map as $modelClass => $module) {
            $observer = new ActivityObserver($logService);
            $observer->setModule($module);

            // IMPORTANT: Do NOT use $modelClass::observe($observer) here.
            // Laravel's observe() converts instances to string callables
            // (e.g. "App\Observers\ActivityObserver@updated"), and when the
            // event fires, createClassCallable() resolves a FRESH instance
            // from the container — losing the $module set by setModule().
            // Register closures directly to capture $observer by reference.
            // Note: prepareListeners wraps ALL listeners via makeListener(),
            // which spreads payload as individual args, so the model is
            // received directly as the first (and only) argument.
            foreach (['created', 'updated', 'deleted'] as $methodName) {
                $modelClass::{$methodName}(function ($model) use ($observer, $methodName) {
                    if ($model) {
                        $observer->{$methodName}($model);
                    }
                });
            }
        }
    }
}
