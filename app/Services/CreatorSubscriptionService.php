<?php
// app/Services/CreatorSubscriptionService.php

namespace App\Services;

use App\Models\CreatorPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use App\Exceptions\CreatorNotOnboardedException;


class CreatorSubscriptionService
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Start Checkout for follower on a creator's plan (connected account).
     * Returns the hosted url.
     */
    public function startCheckout(User $follower, CreatorPlan $plan, string $successUrl, string $cancelUrl): string
    {
        $acct = $plan->creator->profile?->stripe_account_id;
        if (! $acct) throw new \RuntimeException('Creator is not onboarded to Stripe.');

        if (! $plan->stripe_price_id) throw new \RuntimeException('Plan does not have a Stripe price.');

        // Create a Checkout Session on the CONNECTED ACCOUNT
        $session = $this->stripe->checkout->sessions->create([
            'mode'        => 'subscription',
            'line_items'  => [[ 'price' => $plan->stripe_price_id, 'quantity' => 1 ]],
            'success_url' => $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
            'metadata'    => [
                'follower_id' => (string) $follower->id,
                'creator_id'  => (string) $plan->creator_id,
                'plan_id'     => (string) $plan->id,
            ],
            // Let Stripe create a Customer in the connected account automatically
            // (you can also pass 'customer' if you maintain one per connected acct)
        ], ['stripe_account' => $acct]);

        return $session->url;
    }

    /**
     * After success redirect, retrieve the session (connected account) to persist subscription.
     */
    public function persistAfterSuccess(User $follower, string $sessionId): Subscription
    {
        // We need the connected account; store it temporarily in state or infer from session metadata.
        // Safer: get session from platform? No—session lives in connected acct.
        // We'll first try to fetch without account; if it fails, you should pass acct via route or state.
        // Here we assume you passed 'acct' as a query parameter (recommended).
        $acct = request()->string('acct')->value(); // e.g., you add &acct=acct_... to successUrl
        if (! $acct) throw new \RuntimeException('Missing stripe_account_id in success URL.');

        $session = $this->stripe->checkout->sessions->retrieve($sessionId, [], ['stripe_account' => $acct]);

        $creatorId = (int)($session->metadata->creator_id ?? 0);
        $planId    = (int)($session->metadata->plan_id ?? 0);
        $customer  = (string)($session->customer ?? '');
        $subId     = (string)($session->subscription ?? '');

        if (! $creatorId || ! $planId || ! $subId) {
            throw new \RuntimeException('Checkout session missing required data.');
        }

        // Get live subscription details (period end / status)
        $sub = $this->stripe->subscriptions->retrieve($subId, [], ['stripe_account' => $acct]);

        return Subscription::updateOrCreate(
            ['stripe_subscription_id' => $subId],
            [
                'user_id'            => $follower->id,
                'creator_id'         => $creatorId,
                'creator_plan_id'    => $planId,
                'stripe_account_id'  => $acct,
                'stripe_customer_id' => $customer,
                'status'             => (string)$sub->status,
                'cancel_at_period_end' => (bool)$sub->cancel_at_period_end,
                'current_period_end' => isset($sub->current_period_end) ? now()->createFromTimestamp($sub->current_period_end) : null,
            ]
        );
    }

    /**
     * Cancel at period end.
     */
    public function cancelAtPeriodEnd(Subscription $subscription): Subscription
    {
        if (! $subscription->stripe_subscription_id || ! $subscription->stripe_account_id) {
            throw new \RuntimeException('Invalid subscription.');
        }

        $sub = $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true],
            ['stripe_account' => $subscription->stripe_account_id]
        );

        $subscription->update([
            'status'               => (string)$sub->status,
            'cancel_at_period_end' => (bool)$sub->cancel_at_period_end,
            'current_period_end'   => isset($sub->current_period_end) ? now()->createFromTimestamp($sub->current_period_end) : null,
        ]);

        return $subscription;
    }

     /**
     * Get the creator's Stripe Connected Account ID or fail.
     *
     * @throws \App\Exceptions\CreatorNotOnboardedException
     */
    public function connectedAccountOrFail(User $creator): string
    {
        // Defensive: make sure the profile relation is loaded/available
        $profile = $creator->profile;

        // 1) Must have a profile and a saved Connect account ID
        $acct = $profile?->stripe_account_id;
        if (empty($acct)) {
            throw new CreatorNotOnboardedException('Creator is not onboarded to Stripe Connect.');
        }       

        return $acct;
    }

}
