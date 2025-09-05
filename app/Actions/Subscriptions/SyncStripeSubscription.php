<?php

namespace App\Actions\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SyncStripeSubscription
{
    public function __construct(private StripeClient $stripe) {}

    /** Best-effort: hydrate from Checkout Session id (expands ->subscription) */
    public function fromSessionId(string $sessionId): ?Subscription
    {
        $session = $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'customer']
        ]);

        if (!$session || $session->mode !== 'subscription' || !$session->subscription) {
            return null;
        }

        return $this->persist($session->subscription);
    }

    /** Canonical path: hydrate from subscription id */
    public function fromSubscriptionId(string $subId): ?Subscription
    {
        $stripeSub = $this->stripe->subscriptions->retrieve($subId);
        return $this->persist($stripeSub);
    }

    /** Upsert into local DB. */
    private function persist($stripeSub): Subscription
    {
        // These were set in StripeConnectService::startCheckout -> subscription_data.metadata
        $meta = $stripeSub->metadata ?? (object)[];
        $creatorId    = (int) ($meta->creator_id ?? 0);
        $subscriberId = (int) ($meta->subscriber_id ?? 0);
        $planId       = (int) ($meta->plan_id ?? 0);

        if (!$creatorId || !$subscriberId || !$planId) {
            // Fallback: you could resolve creator/plan by $stripeSub->items->data[0]->price->id, etc.
            Log::warning('Missing subscription metadata; cannot upsert cleanly', [
                'sub' => $stripeSub->id,
                'meta' => $meta,
            ]);
        }

        return Subscription::updateOrCreate(
            [
                'subscriber_id' => $subscriberId,
                'creator_id'    => $creatorId,
            ],
            [
                'creator_plan_id'        => $planId,
                'stripe_subscription_id' => $stripeSub->id,
                'stripe_customer_id'     => $stripeSub->customer,
                'stripe_account_id'      => optional($stripeSub->transfer_data)->destination ?? '',
                'status'                 => $stripeSub->status,
                'current_period_start'   => $this->toTs($stripeSub->current_period_start ?? null),
                'current_period_end'     => $this->toTs($stripeSub->current_period_end ?? null),
                'cancel_at_period_end'   => (bool) ($stripeSub->cancel_at_period_end ?? false),
            ]
        );
    }

    private function toTs($unix)
    {
        return $unix ? now()->setTimestamp($unix) : null;
    }
}
