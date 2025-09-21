<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ConnectService
{
    public function __construct(private readonly StripeClient $stripe)
    {
        // The StripeClient is bound in StripeServiceProvider using services.stripe.secret
    }

    /**
     * Create a one-time, time-limited Stripe Express onboarding link for the creator.
     * - Ensures a connected account exists.
     * - Builds refresh/return URLs.
     * - Returns the hosted onboarding URL (string).
     *
     * @throws \Throwable on Stripe/API errors (catch in controller and show friendly message)
     */
    public function createOnboardingLink(User $creator): string
    {        
        // 2.1 Ensure the creator has a connected account
        $acctId = $creator->profile?->stripe_account_id ?? $this->ensureAccount($creator);
        
        // 2.2 Build refresh/return URLs
        // refresh_url: when a single-use link is expired/abandoned, Stripe will send the user here
        $refreshUrl = URL::temporarySignedRoute(
            'connect.start',
            now()->addMinutes(30),
            ['creator' => $creator->id]
        );

        // return_url: after completing onboarding on Stripe, user is redirected here
        $returnUrl = route('connect.return', ['creator' => $creator->id]);

        // 2.3 Call Stripe to create the onboarding link for the CONNECTED account (not platform)
        $link = $this->stripe->accountLinks->create([
            'account'     => $acctId,
            'type'        => 'account_onboarding',
            'refresh_url' => $refreshUrl,
            'return_url'  => $returnUrl,
        ]);

        // 2.4 Give the controller the hosted URL
        return $link->url;
    }

    /**
     * Create a Stripe Express Dashboard login link for the creator.
     * Useful after onboarding to let them manage bank/tax/payouts.
     */
    public function createDashboardLink(User $creator): string
    {
        $acctId = $creator->profile?->stripe_account_id ?? $this->ensureAccount($creator);

        $login = $this->stripe->accounts->createLoginLink($acctId);

        return $login->url;
    }

    /**
     * Ensure a Stripe Connect Express account exists for the creator and return its id.
     * Stores the id on user_profiles.stripe_account_id for reuse.
     */
    private function ensureAccount(User $creator): string
    {
        $profile = $creator->profile ?? UserProfile::firstOrCreate(['user_id' => $creator->id]);

        if ($profile->stripe_account_id) {
            return $profile->stripe_account_id;
        }

        $country = $profile->country ?: 'US';

        $acct = $this->stripe->accounts->create([
            'type' => 'express',
            'country' => $country,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'business_type' => 'individual', // adjust if you collect 'company'
            'metadata' => [
                'creator_id' => (string) $creator->id,
            ],
        ]);

        $profile->update(['stripe_account_id' => $acct->id]);

        return $acct->id;
    }

    /**
     * Pull latest account flags from Stripe and persist them on the profile.
     * You can call this after return_url OR rely on account.updated webhook.
     */
    public function syncAccountStatus(User $creator): void
    {
        $acctId = $creator->profile?->stripe_account_id;
        if (! $acctId) {
            return;
        }

        try {
            $acct = $this->stripe->accounts->retrieve($acctId, []);
        } catch (\Throwable $e) {
            Log::warning('connect.syncAccountStatus.failed', [
                'creator_id' => $creator->id,
                'account_id' => $acctId,
                'err'        => $e->getMessage(),
            ]);
            return;
        }

        $deadline = $acct->requirements->current_deadline ?? null;

        $creator->profile->update([
            'stripe_charges_enabled'      => (bool) $acct->charges_enabled,
            'stripe_payouts_enabled'      => (bool) $acct->payouts_enabled,
            'stripe_onboarded_at'         => ($acct->charges_enabled && $acct->payouts_enabled && ! $deadline)
                ? now()
                : $creator->profile->stripe_onboarded_at,
            'stripe_requirements_due_at'  => $deadline ? now()->createFromTimestamp($deadline) : null,
            'country'                     => $creator->profile->country ?: ($acct->country ?? null),
            'default_currency'            => $creator->profile->default_currency ?: ($acct->default_currency ?? null),
        ]);
    }
}
