<?php

use App\Http\Middleware\CreatorOnly;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Middleware\Authenticate;
use Spatie\Permission\Middleware\RoleMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stripe\StripeClient;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/creator.php',
            __DIR__.'/../routes/stripe.php',
            __DIR__.'/../routes/subscription.php',
            __DIR__.'/../routes/post.php',
            __DIR__.'/../routes/creator_payouts.php',
            __DIR__.'/../routes/purchase.php',
            __DIR__.'/../routes/cancel_subscriptions.php',
            __DIR__.'/../routes/connect_onboarding.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'creator' => CreatorOnly::class,
            'auth' => Authenticate::class, // Laravel's default auth middleware is already aliased by default
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        App\Providers\StripeServiceProvider::class,
    ])
    ->create();

/**
 * Bind StripeClient once for the whole app.
 * The config() call runs when the client is actually resolved.
 */
$app->singleton(StripeClient::class, function () {
    return new StripeClient(config('services.stripe.secret'));
});

return $app;
