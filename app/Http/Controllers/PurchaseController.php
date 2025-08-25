<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserProfile; // your Creator model
use Stripe\StripeClient;

class PurchaseController extends Controller
{
    public function __construct(private StripeClient $stripe) {}

    public function show(UserProfile $creator, Request $request)
    {
        abort_unless($creator->is_creator && $creator->stripe_account_id, 404);

        // Publishable key for Stripe.js
        $pk = config('services.stripe.key');
		$percent = config('services.stripe.application_fee_amount');

        return view('purchase.checkout', [
            'creator' => $creator,
            'stripePublishableKey' => $pk,
            // Optional: default price (USD) to pre-fill the form
            'defaultPrice' => 9.99,
            // Your fee model (example: 15% platform fee, min $0.50)
            'platformFeePercent' => $percent,
            'platformFeeMin' => 0.50,
        ]);
    }

    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'creator_id' => ['required','integer','exists:user_profiles,id'],
            // Amount sent from the client as dollars; validate sensible range
            'amount'     => ['required','numeric','min:0.50','max:9999'],
        ]);

        /** @var \App\Models\User $creator */
        $creator = UserProfile::findOrFail($validated['creator_id']);
        abort_unless($creator->is_creator && $creator->stripe_account_id, 422);

        // Convert dollars -> cents (integers!)
        $amountCents = (int) round($validated['amount'] * 100);

        // Compute your platform fee (EXAMPLE: 15% with a 50¢ minimum)
        $fee = $this->computePlatformFeeCents($amountCents, 15, 50);

        // Create PaymentIntent as a destination charge
        $intent = $this->stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'usd',
            'automatic_payment_methods' => ['enabled' => true],
            'application_fee_amount' => $fee,
            'transfer_data' => [
                'destination' => $creator->stripe_account_id,
            ],
            'metadata' => [
                'creator_id' => (string) $creator->id,
                'buyer_id'   => (string) $request->user()->id,
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
        ]);
    }

    private function computePlatformFeeCents(int $amountCents, int $percent = 15, int $minCents = 50): int
    {
        $calc = (int) round($amountCents * ($percent / 100));
        return max($calc, $minCents);
    }
}

