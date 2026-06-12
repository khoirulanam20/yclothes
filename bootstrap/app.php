<?php

use App\Http\Middleware\EnsureCustomerIsVerified;
use App\Http\Middleware\EnsureHasPermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyOrderAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            return route('customer.login');
        });
        $middleware->validateCsrfTokens(except: [
            'midtrans/notification',
            'doku/notification',
            'klikqris/notification',
        ]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'permission' => EnsureHasPermission::class,
            'admin.activity' => LogAdminActivity::class,
            'customer.verified' => EnsureCustomerIsVerified::class,
            'order.access' => VerifyOrderAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
