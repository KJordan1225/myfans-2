<?php

namespace App\Services;

use App\Models\User;
use Stripe\StripeClient;
use App\Models\UserProfile;

class StripeConnectService
{
    public function __construct(private StripeClient $stripe) {}

    public function ensureExpressAccount(UserProfile $profile): string
    {
        if ($profile->stripe_account_id) return $profile->stripe_account_id;

        $acct = $this->stripe->accounts->create([
            'type' => 'express',
            'country' => 'US',
            'email' => $profile->user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'metadata' => ['user_id' => (string)$profile->user->id],
        ]);

        $profile->forceFill(['stripe_account_id' => $acct->id])->save();
        return $acct->id;
    }

    public function onboardingLink(UserProfile $profile): string
    {
        $accountId = $this->ensureExpressAccount($profile);

        $link = $this->stripe->accountLinks->create([
            'account'     => $accountId,
            'refresh_url' => route('creator.payouts.refresh'),
            'return_url'  => route('creator.payouts.return'),
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function dashboardLoginUrl(UserProfile $profile): string
    {
        if (!$profile->stripe_account_id) $this->ensureExpressAccount($profile);
        $login = $this->stripe->accounts->createLoginLink($profile->stripe_account_id);
        return $login->url;
    }

    public function fetchAccount(UserProfile $profile)
    {
        if (!$profile->stripe_account_id) return null;
        return $this->stripe->accounts->retrieve($profile->stripe_account_id, []);
    }
}
