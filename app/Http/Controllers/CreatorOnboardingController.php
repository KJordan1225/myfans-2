<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\StripeClient;

class CreatorOnboardingController extends Controller
{
    public function start(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile; // adjust to your relation

        $stripe = new StripeClient(config('services.stripe.secret'));

        // 1) If they already have an account, just send them to onboarding to complete/refresh
        if ($profile->stripe_account_id) {
            return $this->redirectToOnboarding($stripe, $profile->stripe_account_id);
        }

        // 2) Create Express account once per creator
        $account = $stripe->accounts->create([
            'type' => 'express',
            'country' => 'US', // or derive dynamically
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            // You can prefill business_type, email, etc.
            'email' => $user->email,
        ]);

        $profile->update(['stripe_account_id' => $account->id]);

        return $this->redirectToOnboarding($stripe, $account->id);
    }

    public function refresh(Request $request)
    {
        // Optionally recreate the onboarding link if user abandoned flow
        $user = $request->user();
        $acct = $user->profile->stripe_account_id;
        abort_if(!$acct, 404);

        $stripe = new StripeClient(config('services.stripe.secret'));
        return $this->redirectToOnboarding($stripe, $acct);
    }

    public function success(Request $request)
    {
        // Called after Stripe onboarding RETURN_URL
        // Optional: fetch account to check requirements
        $stripe = new StripeClient(config('services.stripe.secret'));
        $acct = $request->user()->profile->stripe_account_id;

        if ($acct) {
            $account = $stripe->accounts->retrieve($acct, []);
            // You can inspect $account->requirements->currently_due / eventually_due
            // and show a “You’re ready” or “Finish setup” message.
        }

        return redirect()->route('dashboard')->with('success', 'Payouts setup updated.');
    }

    protected function redirectToOnboarding(StripeClient $stripe, string $acctId)
    {
        $link = $stripe->accountLinks->create([
            'account'     => $acctId,
            'refresh_url' => route('creator.onboarding.refresh'),
            'return_url'  => route('creator.onboarding.success'),
            'type'        => 'account_onboarding',
        ]);

        return redirect()->away($link->url);
    }
}
