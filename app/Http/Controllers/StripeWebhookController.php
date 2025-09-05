<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;
use App\Actions\Subscriptions\SyncStripeSubscription;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeClient $stripe,
        private SyncStripeSubscription $sync,
    ) {}


     public function handle(Request $request)
    {
        // ... verify signature (as you already do) ...

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                if ($session->mode === 'subscription') {
                    $this->sync->fromSessionId($session->id);
                }
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $sub = $event->data->object;
                $this->sync->fromSubscriptionId($sub->id);
                break;

            // optional: invoice.payment_* if you want
        }

        return response()->json(['status' => 'ok']);
    }


    private function upsertSubscriptionFromSession(string $sessionId): void
    {
        $session = $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'customer']
        ]);

        $subscription = $session->subscription; // expanded \Stripe\Subscription
        $this->persistSubscription($subscription);
    }

    private function upsertSubscriptionFromStripeId(string $stripeSubscriptionId): void
    {
        $subscription = $this->stripe->subscriptions->retrieve($stripeSubscriptionId);
        $this->persistSubscription($subscription);
    }

    private function persistSubscription($stripeSub): void
    {
        // Metadata set in subscription_data when creating Checkout session
        $meta = $stripeSub->metadata ?? (object)[];
        $creatorId    = (int) ($meta->creator_id ?? 0);
        $subscriberId = (int) ($meta->subscriber_id ?? 0);
        $planId       = (int) ($meta->plan_id ?? 0);

        if (!$creatorId || !$subscriberId || !$planId) {
            // As a fallback, you can look up by price/product if needed
        }

        Subscription::updateOrCreate(
            [
                'subscriber_id' => $subscriberId,
                'creator_id'    => $creatorId,
            ],
            [
                'creator_plan_id'       => $planId,
                'stripe_subscription_id'=> $stripeSub->id,
                'stripe_customer_id'    => $stripeSub->customer,
                'stripe_account_id'     => optional($stripeSub->transfer_data)->destination ?? '',
                'status'                => $stripeSub->status,
                'current_period_start'  => $this->unixToTs($stripeSub->current_period_start ?? null),
                'current_period_end'    => $this->unixToTs($stripeSub->current_period_end ?? null),
                'cancel_at_period_end'  => (bool) ($stripeSub->cancel_at_period_end ?? false),
            ]
        );
    }

    private function unixToTs($unix)
    {
        return $unix ? now()->setTimestamp($unix) : null;
    }
}
