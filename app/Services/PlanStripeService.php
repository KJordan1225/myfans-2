<?php
// app/Services/PlanStripeService.php

namespace App\Services;

use App\Models\CreatorPlan;
use App\Models\User;
use Stripe\StripeClient;
use App\Exceptions\CreatorNotOnboardedException;

class PlanStripeService
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Ensure Product exists on the creator's connected account.
     */
    public function ensureProduct(CreatorPlan $plan): string
    {
        $acct = $this->connectedAccountOrFail($plan->creator);

        if (!$plan->stripe_product_id) {
            $product = $this->stripe->products->create([
                'name' => "{$plan->creator->name} — {$plan->name}",
                'active' => (bool)$plan->is_active,
                'metadata' => [
                    'creator_id' => (string)$plan->creator_id,
                    'plan_id'    => (string)$plan->id,
                ],
            ], ['stripe_account' => $acct]);

            $plan->stripe_product_id = $product->id;
            $plan->save();
        } else {
            // keep product active flag in sync
            $this->stripe->products->update($plan->stripe_product_id, [
                'active' => (bool)$plan->is_active,
            ], ['stripe_account' => $acct]);
        }

        return $plan->stripe_product_id;
    }

    /**
     * Ensure a Price with the correct amount/interval exists (immutable),
     * creating a new one if values changed.
     */
    public function ensurePrice(CreatorPlan $plan): string
    {
        $acct = $this->connectedAccountOrFail($plan->creator);
        $productId = $this->ensureProduct($plan);

        // Always create a fresh price if any of these changed
        $price = $this->stripe->prices->create([
            'unit_amount' => $plan->price_cents,
            'currency'    => $plan->currency,
            'recurring'   => ['interval' => $plan->interval],
            'product'     => $productId,
            'active'      => (bool)$plan->is_active,
            'metadata'    => [
                'creator_id' => (string)$plan->creator_id,
                'plan_id'    => (string)$plan->id,
            ],
        ], ['stripe_account' => $acct]);

        $plan->stripe_price_id = $price->id;
        $plan->save();

        return $plan->stripe_price_id;
    } 

    private function connectedAccountOrFail(User $creator): string
    {
        $acct = $creator->profile?->stripe_account_id;
        if (!$acct) {
            throw new CreatorNotOnboardedException();
        }        
        return $acct;
    }
}
