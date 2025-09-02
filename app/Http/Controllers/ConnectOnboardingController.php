<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;

class ConnectOnboardingController extends Controller
{
    public function __construct(private StripeClient $stripe) {}

    public function start(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile; // assume hasOne User->profile

        if (!$profile->stripe_account_id) {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'US',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers'     => ['requested' => true],
                ],
                'metadata' => ['user_id' => (string) $user->id],
            ]);

            $profile->update(['stripe_account_id' => $account->id]);
        }

        $link = $this->stripe->accountLinks->create([
            'account' => $profile->stripe_account_id,
            'refresh_url' => route('connect.refresh'),
            'return_url'  => route('connect.return'),
            'type' => 'account_onboarding',
        ]);

        return redirect($link->url);
    }

    public function refresh() { return redirect()->route('connect.start'); }

    public function return(Request $request)
    {
        $user = Auth::user();
        $acct = $this->stripe->accounts->retrieve($user->profile->stripe_account_id);

        $user->profile->update([
            'charges_enabled'   => (bool) $acct->charges_enabled,
            'details_submitted' => (bool) $acct->details_submitted,
        ]);

        return redirect()->route('dashboard')->with('success','Stripe account connected.');
    }

}
