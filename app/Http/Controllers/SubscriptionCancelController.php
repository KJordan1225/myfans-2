<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;


class SubscriptionCancelController extends Controller
{
    public function __construct(private StripeClient $stripe) {}

    // Safer “end-of-period” cancel (recommended UX)
    public function cancelAtPeriodEnd(Subscription $subscription, Request $request)
    {
        dd($subscription);
        $this->authorizeFan($subscription);
        
        $opts = $this->optsFor($subscription); // adds stripe_account when needed

        // Set cancel_at_period_end = true
        $sub = $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true],
            $opts + ['idempotency_key' => $this->idemKey($subscription, 'later')]
        );
        
        $subscription->update([
            'cancel_at_period_end' => (bool) $sub->cancel_at_period_end,
            'status'               => $sub->status, // might still be 'active'
        ]);
        
        return to_route('subscriptions.index')->with('success', 'Cancellation scheduled at period end.');
    }

    // Immediate cancel (refunds/proration are your policy decision)
    public function cancelNow(Subscription $subscription, Request $request)
    {
        $this->authorizeFan($subscription);

        $opts = $this->optsFor($subscription);

        // Cancel now
        $sub = $this->stripe->subscriptions->cancel(
            $subscription->stripe_subscription_id,
            [], // extra params, e.g. ['invoice_now' => true, 'prorate' => true]
            $opts + ['idempotency_key' => $this->idemKey($subscription, 'now')]
        );

        $subscription->update([
            'cancel_at_period_end' => (bool) $sub->cancel_at_period_end,
            'status'               => $sub->status, // usually 'canceled'
            'canceled_at'          => now(),
        ]);

        return to_route('subscriptions.index')->with('success', 'Subscription canceled.');
    }

    private function authorizeFan(Subscription $subscription): void
    {       
        abort_unless($subscription->user_id === Auth::id(), 403);
    }

    /**
     * If the subscription lives on a connected account, include the header:
     * ['stripe_account' => 'acct_...']
     * If it's platform-managed, return [].
     */
    private function optsFor(Subscription $subscription): array
    {
        return $subscription->stripe_account_id
            ? ['stripe_account' => $subscription->stripe_account_id]
            : [];
    }

    private function idemKey(Subscription $subscription, string $suffix): string
    {
        return "cancel:{$subscription->stripe_subscription_id}:user:".Auth::id().":{$suffix}";
    }

}
