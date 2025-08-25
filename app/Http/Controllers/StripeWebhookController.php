<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }

        if ($event->type === 'account.updated') {
            $acct = $event->data->object; // \Stripe\Account
            $user_profile = UserProfile::where('stripe_account_id', $acct->id)->first();
            if ($user_profile) {
                $user_profile->forceFill([
                    'stripe_charges_enabled'   => (bool)$acct->charges_enabled,
                    'stripe_payouts_enabled'   => (bool)$acct->payouts_enabled,
                    'stripe_details_submitted' => (bool)$acct->details_submitted,
                    'stripe_default_currency'  => $acct->default_currency ?? null,
                    'stripe_requirements'      => $acct->requirements ? $acct->requirements->toArray() : null,
                ])->save();
            }
        }

        // add other events as needed (payout.paid, payout.failed, etc.)
        return response('OK', 200);
    }
}

