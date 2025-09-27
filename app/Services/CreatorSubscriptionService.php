<?php
// app/Services/CreatorSubscriptionService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\Subscription;
use Stripe\StripeClient;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Exceptions\CreatorNotOnboardedException;

class CreatorSubscriptionService
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Start a Stripe Checkout (mode: subscription) on the CONNECTED ACCOUNT.
     *
     * - $creator MUST be onboarded -> has stripe_account_id
     * - Prefer using a price that belongs to the same connected account.
     * - If $plan->stripe_price_id is missing or mismatched, we can inline price_data.
     *
     * Returns the hosted Checkout URL string.
     */
    public function startCheckout(
        User $follower,
        CreatorPlan $plan,
        string $successUrl,  // Should contain ?session_id={CHECKOUT_SESSION_ID}&acct={acct_...}
        string $cancelUrl,
        ?float $applicationFeePercent = null,    // or use a fixed amount variant below
        ?int $applicationFeeAmountCents = null,  // mutually exclusive with percent
    ): string {
        $acct = $this->connectedAccountOrFail($plan->creator);

        // (1) Resolve a customer on the connected account (one per follower per acct)
        $customerId = $this->ensureConnectedCustomer($acct, $follower);

        // (2) Resolve pricing on the same connected account
        [$lineItem, $priceSource] = $this->resolveLineItemForAccount($acct, $plan);

        // (3) Build subscription_data with optional platform fee
        $subscriptionData = [];
        if (!is_null($applicationFeePercent)) {
            $subscriptionData['application_fee_percent'] = $applicationFeePercent;
        } elseif (!is_null($applicationFeeAmountCents)) {
            $subscriptionData['application_fee_amount'] = $applicationFeeAmountCents;
        }

        // (4) Create the Checkout Session on the CONNECTED account
        //     Use idempotency key for safety on retries
        $idempotencyKey = 'co_sub_' . Str::uuid();

        $session = $this->stripe->checkout->sessions->create(
            [
                'mode'         => 'subscription',
                'success_url'  => $this->ensureSuccessUrlHasSessionIdAndAcct($successUrl, $acct),
                'cancel_url'   => $cancelUrl,
                'customer'     => $customerId, // guarantees the sub is tied to this customer's record on the connected account
                'line_items'   => [$lineItem], // resolved properly for this account
                'subscription_data' => $subscriptionData,
                'metadata' => [
                    'follower_id' => (string) $follower->id,
                    'creator_id'  => (string) $plan->creator_id,
                    'plan_id'     => (string) $plan->id,
                    'price_source'=> $priceSource, // 'price' or 'price_data_inline'
                ],
            ],
            [
                'stripe_account' => $acct,
                'idempotency_key' => $idempotencyKey,
            ]
        );

        return $session->url;
    }

    /**
     * After the success redirect, persist the Subscription in your DB.
     * You must pass the same connected account ($acct) used during Checkout.
     */
    public function persistAfterSuccess(User $follower, string $sessionId, string $acct): Subscription
    {
        if (empty($acct)) {
            throw new \RuntimeException('Missing stripe_account_id when retrieving Checkout Session.');
        }

        // Retrieve the session from the CONNECTED account
        $session = $this->stripe->checkout->sessions->retrieve($sessionId, [], ['stripe_account' => $acct]);

        $creatorId = (int)($session->metadata->creator_id ?? 0);
        $planId    = (int)($session->metadata->plan_id ?? 0);
        $customer  = (string)($session->customer ?? '');
        $subId     = (string)($session->subscription ?? '');

        if (!$creatorId || !$planId || !$subId) {
            throw new \RuntimeException('Checkout session missing required metadata (creator_id/plan_id/sub).');
        }

        // Pull live subscription from the CONNECTED account
        $remoteSub = $this->stripe->subscriptions->retrieve($subId, [], ['stripe_account' => $acct]);

        return Subscription::updateOrCreate(
            ['stripe_subscription_id' => $subId],
            [
                'user_id'              => $follower->id,
                'creator_id'           => $creatorId,
                'creator_plan_id'      => $planId,
                'stripe_account_id'    => $acct,
                'stripe_customer_id'   => $customer,
                'status'               => (string) $remoteSub->status,
                'cancel_at_period_end' => (bool)   $remoteSub->cancel_at_period_end,
                'current_period_end'   => isset($remoteSub->current_period_end)
                    ? Carbon::createFromTimestamp($remoteSub->current_period_end)
                    : null,
            ]
        );
    }

    /**
     * Cancel at period end on the CONNECTED account.
     */
    public function cancelAtPeriodEnd(Subscription $subscription): Subscription
    {
        $this->guardStripeKeys($subscription);

        $remote = $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true],
            ['stripe_account' => $subscription->stripe_account_id]
        );

        $subscription->update([
            'status'               => (string) $remote->status,
            'cancel_at_period_end' => (bool)   $remote->cancel_at_period_end,
            'current_period_end'   => isset($remote->current_period_end)
                ? Carbon::createFromTimestamp($remote->current_period_end)
                : null,
        ]);

        return $subscription;
    }

    /**
     * OPTIONAL: Immediate cancellation (proration rules depend on your Stripe settings).
     */
    public function cancelImmediately(Subscription $subscription): Subscription
    {
        $this->guardStripeKeys($subscription);

        $remote = $this->stripe->subscriptions->cancel(
            $subscription->stripe_subscription_id,
            [],
            ['stripe_account' => $subscription->stripe_account_id]
        );

        $subscription->update([
            'status'               => (string) $remote->status,
            'cancel_at_period_end' => (bool)   $remote->cancel_at_period_end,
            'current_period_end'   => isset($remote->current_period_end)
                ? Carbon::createFromTimestamp($remote->current_period_end)
                : null,
        ]);

        return $subscription;
    }

    /**
     * Ensure the creator is onboarded to Connect and return acct_...
     *
     * @throws CreatorNotOnboardedException
     */
    public function connectedAccountOrFail(User $creator): string
    {
        $acct = $creator->profile?->stripe_account_id;
        if (empty($acct)) {
            throw new CreatorNotOnboardedException('Creator is not onboarded to Stripe Connect.');
        }
        return $acct;
    }

    /* =======================
     * Internal helper methods
     * ======================= */

    /**
     * Ensure a Customer exists on the CONNECTED account for this follower.
     * Strategy:
     *  - If you store a per-account customer mapping, return it.
     *  - Otherwise, create one with follower's email and save mapping in your DB.
     *
     * For brevity, this demo uses follower email + metadata.
     */
    protected function ensureConnectedCustomer(string $acct, User $follower): string
    {
        // If you maintain a map table: connected_customers(user_id, acct, customer_id)
        // look it up first and return. Otherwise create:
        $customer = $this->stripe->customers->create(
            [
                'email'    => $follower->email,
                'metadata' => [
                    'app_user_id' => (string) $follower->id,
                ],
            ],
            ['stripe_account' => $acct]
        );

        // TODO: persist mapping to avoid creating duplicates:
        // ConnectedCustomer::updateOrCreate(
        //   ['user_id' => $follower->id, 'stripe_account_id' => $acct],
        //   ['stripe_customer_id' => $customer->id]
        // );

        return $customer->id;
    }

    /**
     * Build a line_item for this connected account:
     *  - If $plan->stripe_price_id belongs to $acct, use it
     *  - Else, inline price_data to guarantee account ownership
     *
     * Returns: [array $lineItem, string $source] where $source is 'price'|'price_data_inline'
     */
    protected function resolveLineItemForAccount(string $acct, CreatorPlan $plan): array
    {
        if (!empty($plan->stripe_price_id)) {
            // Best-effort probe: if it 404s, we’ll fall back to inline price_data
            try {
                $this->stripe->prices->retrieve($plan->stripe_price_id, [], ['stripe_account' => $acct]);

                return [[
                    'price'    => $plan->stripe_price_id,
                    'quantity' => 1,
                ], 'price'];
            } catch (\Throwable $e) {
                // fall through to inline price_data
            }
        }

        // Inline price_data—guaranteed to be created under the connected account for this Session
        if (empty($plan->price_cents) || empty($plan->name)) {
            throw new \RuntimeException('Plan is missing local price fields required for inline price_data.');
        }

        return [[
            'price_data' => [
                'currency'     => $plan->currency ?? 'usd',
                'unit_amount'  => (int) $plan->price_cents,
                'recurring'    => ['interval' => $plan->interval ?? 'month'],
                'product_data' => ['name' => $plan->name],
            ],
            'quantity' => 1,
        ], 'price_data_inline'];
    }

    /**
     * Ensure success URL contains both session_id placeholder and acct.
     * If missing, we append them.
     */
    protected function ensureSuccessUrlHasSessionIdAndAcct(string $successUrl, string $acct): string
    {
        $hasSession = str_contains($successUrl, '{CHECKOUT_SESSION_ID}');
        $hasAcct    = str_contains($successUrl, 'acct=');

        $url = $successUrl;

        if (!$hasSession) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';
        }
        if (!$hasAcct) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'acct=' . urlencode($acct);
        }

        return $url;
    }

    /**
     * Guard that a Subscription has the necessary Stripe identifiers.
     */
    protected function guardStripeKeys(Subscription $subscription): void
    {
        if (empty($subscription->stripe_subscription_id) || empty($subscription->stripe_account_id)) {
            throw new \RuntimeException('Invalid subscription: missing stripe_subscription_id or stripe_account_id.');
        }
    }
}
