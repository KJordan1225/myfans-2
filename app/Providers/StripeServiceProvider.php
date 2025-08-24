<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            return new StripeClient(['api_key' => $app['config']->get('services.stripe.secret')]);
        });
    }

    public function boot(): void
    {
        //
    }

}
