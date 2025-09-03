<?php

namespace App\Services;

use App\Models\CreatorPlan;
use App\Models\User;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class StripeConnectService
{
    public function __construct(private StripeClient $stripe) {}

    /** Create a Stripe product+price for a creator plan and persist IDs. */
    public function createPlanPrice(User $creator, string $name, int $amountCents, string $interval = 'month', string $currency = 'usd', float $platformFeePercent = 15.0): CreatorPlan
    {
        $product = $this->stripe->products->create([
            'name' => $name,
            'metadata' => [ 'creator_id' => (string) $creator->id ],
        ]);

        $price = $this->stripe->prices->create([
            'unit_amount' => $amountCents,
            'currency' => $currency,
            'recurring' => ['interval' => $interval],
            'product' => $product->id,
        ]);

        return CreatorPlan::create([
            'creator_id' => $creator->id,
            'name' => $name,
            'stripe_product_id' => $product->id,
            'stripe_price_id'   => $price->id,
            'amount' => $amountCents,
            'currency' => $currency,
            'interval' => $interval,
            'platform_fee_percent' => $platformFeePercent,
            'active' => true,
        ]);
    }

    /** Ensure the subscriber has a Stripe Customer and return its ID. */
    public function getOrCreateCustomer(User $subscriber): string
    {
        if (!$subscriber->stripe_customer_id) {
            $customer = $this->stripe->customers->create([
                'email' => $subscriber->email,
                'name'  => $subscriber->name,
                'metadata' => [ 'user_id' => (string) $subscriber->id ],
            ]);
            $subscriber->forceFill(['stripe_customer_id' => $customer->id])->save();
        }
        return $subscriber->stripe_customer_id;
    }

    /** Create a Checkout Session for a subscription that pays a connected account. */
    public function startCheckout(User $subscriber, CreatorPlan $plan, string $successUrl, string $cancelUrl): string
    {
        $creator = $plan->creator;
        $accountId = $creator->profile?->stripe_account_id;
        abort_unless($accountId, 422, 'Creator has not finished Stripe onboarding.');

        $customerId = $this->getOrCreateCustomer($subscriber);

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[ 'price' => $plan->stripe_price_id, 'quantity' => 1 ]],
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
            'subscription_data' => [
                'application_fee_percent' => (float) $plan->platform_fee_percent,
                'transfer_data' => [ 'destination' => $accountId ],
                'metadata' => [
                    'creator_id'    => (string) $creator->id,
                    'subscriber_id' => (string) $subscriber->id,
                    'plan_id'       => (string) $plan->id,
                ],
            ],
            'metadata' => [
                'creator_id'    => (string) $creator->id,
                'subscriber_id' => (string) $subscriber->id,
                'plan_id'       => (string) $plan->id,
            ],
            'allow_promotion_codes' => false,
        ]);

        return $session->url; // redirect user to this URL
    }
}
