<?php
// app/Http/Controllers/SubscribeController.php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use App\Models\Subscription;
use App\Services\CreatorSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use App\Exceptions\CreatorNotOnboardedException;

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

        try {
            // this will throw if not onboarded
            $acct = $svc->connectedAccountOrFail($plan->creator);

            $success = route('subscribe.success').'?acct='.$acct;
            $cancel  = route('subscribe.cancelled', ['creator' => $plan->creator_id]);

            $url = $svc->startCheckout($follower, $plan, $success, $cancel);
            return redirect()->away($url);

        } catch (CreatorNotOnboardedException|\RuntimeException $e) {
            // Flash a payload your Blade can feed into Swal.fire(...)
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'Action needed',
                'text'  => $e->getMessage(),
            ]);
        }
    }

    // Success return → persist subscription by reading Checkout Session
    public function success(Request $request, CreatorSubscriptionService $svc)
    {
        $sessionId = $request->string('session_id')->value();
        if (! $sessionId) return redirect()->route('dashboard')->with('warning','Missing session id.');
        
        try {
            $sub = $svc->persistAfterSuccess($request->user(), $sessionId);
            return redirect()->route('subscriptions.mine')
                ->with('success', 'Subscription active. Welcome aboard!');
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }
    }

    // Cancel page (optional friendly page)
    public function cancelled(Request $request, int $creator)
    {
        return redirect()->route('subscribe.show', $creator)->with('warning','Checkout cancelled.');
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
