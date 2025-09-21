<?php
// app/Http/Controllers/SubscribeController.php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use App\Models\Subscription;
use App\Services\CreatorSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\CheckoutSession;
use Stripe\StripeClient;


class SubscribeController extends Controller
{
    public static function middleware(): array
    {
        return [
            // Apply to the entire controller:
            'auth',
            'verified',
        ];
    }

    // Show creator's active plans for a fan to subscribe
    public function showPlans(Request $request, $creatorId)
    {
        $plans = \App\Models\CreatorPlan::where('creator_id', $creatorId)
            ->where('is_active', true)
            ->orderBy('price_cents')
            ->get();

        $creator = \App\Models\User::findOrFail($creatorId);

        return view('subscribe.show', compact('creator','plans'));
    }

    // Start checkout
    public function start(Request $request, CreatorPlan $plan, CreatorSubscriptionService $svc)
    {
        $follower = $request->user();

        $acct = $plan->creator->profile?->stripe_account_id;
        if (! $acct) return back()->with('error','Creator is not onboarded to Stripe.');

        // $success = route('subscribe.success') . '?acct='.$acct;
        $success = route('subscribe.success');
        $cancel  = route('subscribe.cancelled', ['creator' => $plan->creator_id]);

        try {
            $url = $svc->startCheckout($follower, $plan, $success, $cancel);
            // dd($success);
            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Success return → persist subscription by reading Checkout Session
    public function success(Request $request, CreatorSubscriptionService $svc)
    {
               
        $sid = (string) $request->query('session_id', '');
        if (! $sid || ! str_starts_with($sid, 'cs_')) {
            return redirect()->route('dashboard')->with('error', 'Missing or invalid session_id.');
        }

        $row = CheckoutSession::where('session_id', $sid)->first();
        if (! $row) {
            // We didn’t find a local mapping; still show a soft success message,
            // but don’t provision. Webhook will handle the real state.
            return redirect()->route('dashboard')->with('success', 'Payment processed. We\'ll update your access momentarily.');
        }
        

        // Optional: fetch the Checkout Session from Stripe (connected account) for display
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $session = $stripe->checkout->sessions->retrieve(
                $sid,
                [
                    // expand a few useful things for UI
                    'expand' => [
                        'subscription',
                        'line_items.data.price.product',
                        'customer',
                    ],
                ],
                [
                    // **Critical**: tell Stripe which connected account this session lives in
                    'stripe_account' => $row->stripe_account_id,
                ]
            );

            // You can use $session->subscription to show plan, status, etc.
            // But do NOT provision here—trust the webhook. Maybe mark the local
            // row as "completed" just for UX:
            $row->update(['status' => 'completed']);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Subscription successful! You’ll see access unlock shortly.');
            } catch (\Throwable $e) {
            // If fetch fails, fall back to a soft success. Webhook will still finalize.
            return redirect()
                ->route('dashboard')
                ->with('success', 'Payment processed. We\'re finalizing your access.');
        }

    }

    // Cancel page (optional friendly page)
    public function cancelled(Request $request, int $creator)
    {
        // If you want, find the most recent pending session for this follower+creator and mark canceled.
        CheckoutSession::where('follower_id', $request->user()->id)
            ->where('creator_id', $creator)
            ->where('status', 'pending')
            ->latest()->first()?->update(['status' => 'canceled']);

        return redirect()->route('dashboard')->with('error', 'Checkout canceled.');
    }


    // List my subscriptions
    public function mine(Request $request)
    {
        $subs = Subscription::with(['creator','plan'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('subscribe.mine', compact('subs'));
    }

    // Cancel at period end
    public function cancel(Request $request, Subscription $subscription, CreatorSubscriptionService $svc)
    {
        // Only the follower who owns the sub can cancel
        abort_unless($subscription->user_id === $request->user()->id, 403);

        try {
            $svc->cancelAtPeriodEnd($subscription);
            return back()->with('success','Subscription will cancel at period end.');
        } catch (\Throwable $e) {
            return back()->with('error',$e->getMessage());
        }
    }
}
