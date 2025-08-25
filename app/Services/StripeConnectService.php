<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\User;

class StripeConnectService
{
    public function __construct(private StripeClient $stripe) {}

    public function ensureExpressAccount(User $user): string
    {
        if ($user->stripe_account_id) return $user->stripe_account_id;

        $acct = $this->stripe->accounts->create([
            'type' => 'express',
            'country' => 'US',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'metadata' => ['user_id' => (string)$user->id],
        ]);

        $user->forceFill(['stripe_account_id' => $acct->id])->save();
        return $acct->id;
    }

    public function onboardingLink(User $user): string
    {
        $accountId = $this->ensureExpressAccount($user);

        $link = $this->stripe->accountLinks->create([
            'account'     => $accountId,
            'refresh_url' => route('creator.payouts.refresh'),
            'return_url'  => route('creator.payouts.return'),
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function dashboardLoginUrl(User $user): string
    {
        if (!$user->stripe_account_id) $this->ensureExpressAccount($user);
        $login = $this->stripe->accounts->createLoginLink($user->stripe_account_id);
        return $login->url;
    }

    public function fetchAccount(User $user)
    {
        if (!$user->stripe_account_id) return null;
        return $this->stripe->accounts->retrieve($user->stripe_account_id, []);
    }
}
