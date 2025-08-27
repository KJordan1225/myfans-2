<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Stripe\Webhook;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\ProcessedStripeEvent;


class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }
        
        // 1) If we’ve already processed this Stripe event id → exit fast
        if (ProcessedStripeEvent::where('event_id', $event->id)->exists()) {
            return response('OK', 200);
        }	

        // 2) Persist idempotency *and* perform fulfillment atomically
        DB::transaction(function () use ($event) {
            // Re-check inside the txn (race-proof)
            if (ProcessedStripeEvent::where('event_id', $event->id)->lockForUpdate()->exists()) {
                return; // another worker got it first
            }

            // Mark the event as seen now (so retries during this txn are no-ops)
            ProcessedStripeEvent::create(['event_id' => $event->id]);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    /** @var \Stripe\PaymentIntent $pi */
                    $pi = $event->data->object;

                    // Lock the purchase row to avoid concurrent updates
                    $purchase = Purchase::where('payment_intent_id', $pi->id)->lockForUpdate()->first();

                    if (!$purchase) {
                        // Optional: create if you rely purely on webhook (no pre-row)
                        // $purchase = Purchase::create([...]);
                        return;
                    }

                    // 3) Idempotent exit if already fulfilled/succeeded
                    if ($purchase->status === 'succeeded' && $purchase->fulfilled_at) {
                        return; // already processed; do nothing
                    }

                    // (Optional) sanity checks to prevent mismatched PI usage:
                    // if ($purchase->amount_cents !== $pi->amount || $purchase->currency !== $pi->currency) { ... }

                    // 4) Perform your “once-only” side-effects here
                    // e.g., grant access, create entitlements, send notifications, etc.

                    // 5) Mark as succeeded/fulfilled	
                    $purchase->update([
                        'status'       => 'succeeded',
                        'fulfilled_at' => now(),
                        'meta'         => array_merge($purchase->meta ?? [], [
                            'stripe_pi' => $pi->id,
                            'charges'   => $pi->charges?->toArray(),
                        ]),
                    ]);
                    break;

                case 'payment_intent.payment_failed':	
                    $pi = $event->data->object;
                    Purchase::where('payment_intent_id', $pi->id)
                        ->lockForUpdate()
                        ->update(['status' => 'failed']);
                    break;
            }
        });

        return response('OK', 200);
    }

}

