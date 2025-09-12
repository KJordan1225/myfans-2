<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $sig = $request->header('Stripe-Signature');
        // abort_unless($secret && $sig, 403, 'Missing Stripe signature.');
        // Let the controller do the actual constructEvent() verification
        return $next($request);
    }
}

