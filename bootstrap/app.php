<?php

use App\Http\Middleware\DebugModeMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Providers\FortifyServiceProvider;
use App\Providers\TestingServiceProvider;
use HotwiredLaravel\TurboLaravel\Http\Middleware\TurboMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\RouteNotFoundException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Multitenancy\MultitenancyServiceProvider;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            require base_path('routes/web.php');

            // API routes — loaded under the `api` middleware group and `/api` prefix.
            Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));

            require base_path('routes/console.php');
        },
        health: '/up',
    )
    ->withProviders([
        MultitenancyServiceProvider::class,
        FortifyServiceProvider::class,
        TestingServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->prepend(HandleCors::class);

        $middleware->web(append: [
            NeedsTenant::class,
            SetLocale::class,
            DebugModeMiddleware::class,
            SecurityHeaders::class,
            TurboMiddleware::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            SubstituteBindings::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('admin.login'));

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'guest' => RedirectIfAuthenticated::class,
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Turbo Drive sends X-Requested-With: XMLHttpRequest with Accept: */*,
        // which causes expectsJson() to return true. Fix: return HTML for Turbo
        // form submissions so @error directives render correctly and prevent logout.
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }
            if ($request->isXmlHttpRequest() && $request->accepts('text/html')) {
                return false;
            }

            return $request->expectsJson();
        });

        // Helper function to handle errors consistently with Turbo Laravel support
        $shouldJson = function ($request) {
            if ($request->is('api/*')) {
                return true;
            }
            if ($request->isXmlHttpRequest() && $request->accepts('text/html')) {
                return false;
            }

            return $request->expectsJson();
        };
        $handleError = function ($request, $code, $message) use ($shouldJson) {
            if ($shouldJson($request)) {
                return response()->json(['message' => $message], $code);
            }

            return response()->view("errors.{$code}", [], $code);
        };

        // 400 - Bad Request
        $exceptions->render(function (BadRequestException $e, Request $request) use ($handleError) {
            return $handleError($request, 400, 'Bad Request');
        });

        // 403 - Forbidden
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($handleError) {
            return $handleError($request, 403, 'Forbidden');
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($handleError) {
            return $handleError($request, 403, 'Forbidden');
        });

        // 404 - Route Not Found
        $exceptions->render(function (RouteNotFoundException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Not Found Http Exception
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Method not allowed');
        });

        // 419 - CSRF Token Expired
        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($handleError) {
            return $handleError($request, 419, 'Page expired due to inactivity');
        });

        // 429 - Too Many Requests
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($handleError) {
            return $handleError($request, 429, 'Too many requests');
        });

        // No tenant seeded yet
        $exceptions->render(function (NoCurrentTenant $e, Request $request) use ($shouldJson) {
            if ($shouldJson($request)) {
                return response()->json(['message' => 'No workspace configured. Run database seeders.'], 503);
            }

            return redirect()->route('admin.login');
        });

        // Model Not Found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Resource not found');
        });

        // Database connection issues — render 401 (not authenticated) page
        $exceptions->render(function (PDOException $e, Request $request) use ($handleError) {
            return $handleError($request, 401, 'Service temporarily unavailable');
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($handleError) {
            return $handleError($request, 401, 'Service temporarily unavailable');
        });

        // Maintenance mode (503)
        $exceptions->render(function (HttpException $e, Request $request) use ($handleError) {
            if ($e->getStatusCode() === 503) {
                return $handleError($request, 503, 'Maintenance');
            }

            return null;
        });

        // ValidationException — check raw Accept header directly (not expectsJson)
        $exceptions->render(function (ValidationException $e, Request $request) {
            $accept = $request->header('Accept', '');
            Log::info('ValidationException caught', ['accept' => $accept]);
            if (str_contains($accept, 'text/html')) {
                return redirect()->back()
                    ->withErrors($e->errors(), $e->errorBag)
                    ->withInput();
            }
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        });

        // 500 - Server Error (catch-all)
        $exceptions->render(function (Throwable $e, Request $request) use ($handleError) {
            if ($e instanceof TokenMismatchException) {
                return $handleError($request, 419, 'Page expired due to inactivity');
            }
            if ($e instanceof ValidationException) {
                return null;
            }
            if ($e instanceof AuthenticationException) {
                return null;
            }

            return $handleError($request, 500, 'Internal server error');
        });
    })->create();
