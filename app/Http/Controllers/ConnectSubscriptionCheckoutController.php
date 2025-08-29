<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // creator
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;


class ConnectSubscriptionCheckoutController extends Controller
{
    public function __construct(private StripeClient $stripe) {}

    public function start(Request $request, User $creator)
    {
        $request->validate([
            'price_id' => ['required', 'string'],
        ]);

        abort_unless($creator->is_creator && $creator->stripe_account_id, 404);

        // ✅ Security: ensure price_id actually belongs to this creator
        $allowed = DB::table('creator_prices')
            ->where('creator_id', $creator->id)
            ->where('stripe_price_id', $request->price_id)
            ->exists();

        abort_unless($allowed, 422, 'Invalid plan for this creator.');

        // Ensure buyer has a platform Customer
        $buyer = $request->user();
        if (!$buyer->stripe_customer_id) {
            $customer = $this->stripe->customers->create([
                'email'    => $buyer->email,
                'name'     => $buyer->name,
                'metadata' => ['user_id' => (string)$buyer->id],
            ]);
            $buyer->forceFill(['stripe_customer_id' => $customer->id])->save();
        }

        // Hosted subscription Checkout with Connect revenue share
        $session = $this->stripe->checkout->sessions->create([
            'mode'       => 'subscription',
            'customer'   => $buyer->stripe_customer_id,
            'line_items' => [[ 'price' => $request->price_id, 'quantity' => 1 ]],

            'success_url'=> route('purchase.show', $creator->id) . '?status=success',
            'cancel_url' => route('purchase.show', $creator->id) . '?status=cancelled',

            'subscription_data' => [
                'application_fee_percent' => 15,   // your cut each invoice
                'transfer_data' => [
                    'destination' => $creator->stripe_account_id, // route revenue to creator
                ],
                'metadata' => [
                    'creator_id' => (string)$creator->id,
                    'buyer_id'   => (string)$buyer->id,
                ],
            ],
            'metadata' => [
                'creator_id' => (string)$creator->id,
                'buyer_id'   => (string)$buyer->id,
            ],
        ]);

        return redirect()->away($session->url);
    }

}
