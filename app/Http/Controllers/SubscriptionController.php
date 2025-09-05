<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\StripeClient;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\SubscriptionService;
use App\Http\Requests\Creator\UpsertSubscriptionRequest;


class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $service,
        private StripeClient $stripe
    ) {}

    /**
     * Subscriber dashboard: list the current user's active subscriptions.
     * Rule (1): a user can have many subscriptions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $active = $this->service->subscriberActiveSubscriptions($user);
        // Optionally eager-load creator for card rendering
        $active->load(['creator:id,name', 'creator.profile:id,user_id,display_name']);

        return view('subscriptions.index', [
            'subscriptions' => $active,
        ]);
    }

    /**
     * Creator page: show the single subscription plan owned by the current user.
     * Rules (3) & (4): one creator per subscription; one subscription per creator.
     */
    public function creatorShow(Request $request)
    {
        $creator = $request->user();

        // Optional policy: $this->authorize('viewOwnPlan', $creator);
        $plan = $creator->ownedSubscription; // hasOne by creator_id + DB unique index

        return view('creator.subscription.show', [
            'subscription' => $plan,
        ]);
    }

    /**
     * Creator action: create or update the single plan.
     * Enforces "one plan per creator" via unique index + service method.
     */
    public function creatorStoreOrUpdate(UpsertSubscriptionRequest $request)
    {
        $creator = $request->user();
        
        $stripe = new StripeClient(config('services.stripe.secret'));   
        $userProfile = Auth::user()->profile;
        $subscription = Auth::user()->ownedSubscription;
        
        // Create a product
        $product = $stripe->products->create([
            'name' => 'Subscription for ' . $userProfile->display_name,
            'description' => 'Recurring subscription fee to supprt ' . $userProfile->display_name,
        ]);
        
        // Create a price for the product
        $price = $stripe->prices->create([
            'unit_amount' => $subscription->price*100, // in cents
            'currency' => 'usd',
            'recurring' => ['interval' => 'month'],
            'product' => $product->id,
        ]);
        
        // Store $price->id for this creator/tier
        DB::table('creator_prices')->insert([
            'creator_id'      => $creator->id,
            'stripe_price_id' => $price->id,
            'display_name'    => 'Monthly',
            'amount'          => $subscription->price*100, // in cents
        ]);

        // Optional policy: $this->authorize('manageOwnPlan', $creator);
        $subscription = $this->service->createOrUpdateCreatorSubscription($creator, $request->validated());

        return back()->with('success', 'Subscription plan saved.')->with('subscription_id', $subscription->id);
    }

    /**
     * Subscriber action: subscribe to a creator's plan.
     * Prevents self-subscription; snapshots price; sets active status on pivot.
     */
    public function subscribe(Request $request, Subscription $subscription)
    {
        $subscriber = $request->user();
        // Optional policy: $this->authorize('subscribe', [$subscriber, $subscription]);
        $this->service->subscribe($subscriber, $subscription, now(), [
            'provider'                 => 'stripe', // or null if offline
            'provider_subscription_id' => $request->string('provider_subscription_id')->toString() ?: null,
        ]);

        return back()->with('success', 'Subscribed successfully.');
    }

    /**
     * Subscriber action: cancel (soft) a subscription.
     * Keeps history; flips status/is_active on pivot.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->subscriber_id === Auth::id(), 403);

        $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $subscription->update(['cancel_at_period_end' => true]);

        return back()->with('success', 'Your subscription will cancel at period end.');
    }
    

    /**
     * Creator page: list active subscribers to my plan.
     * Rule (2): a subscription can have many users as subscribers.
     */
    public function creatorSubscribers(Request $request)
    {
        $creator = $request->user();
        // Optional policy: $this->authorize('viewSubscribers', $creator);

        $subscribers = $this->service->creatorActiveSubscribers($creator)
            ->load('profile:id,user_id,display_name');

        return view('creator.subscription.subscribers', [
            'subscribers' => $subscribers,
        ]);
    }

    public function createProductAndPrice()
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
       
        $userProfile = Auth::user()->profile;
        $subscription = Auth::user()->ownedSubscription;
        
        // Create a product
        $product = $stripe->products->create([
            'name' => 'Subscription for ' . $userProfile->display_name,
            'description' => 'Recurring subscription fee to supprt ' . $userProfile->display_name,
        ]);

        // Create a price for the product
        $price = $stripe->prices->create([
            'unit_amount' => $subscription->price*100, // in cents
            'currency' => 'usd',
            'recurring' => ['interval' => 'month'],
            'product' => $product->id,
        ]);

        // Store $price->id for this creator/tier
        DB::table('creator_prices')->insert([
            'creator_id'      => $creator->id,
            'stripe_price_id' => $price->id,
            'name'            => 'Monthly',
            'amount'          => $subscription->price*100, // in cents
        ]);


        return [$product, $price];
    }

    /**
     * Cancel at the end of the current period (keeps access until then).
     */
    public function cancelAtPeriodEnd(Request $request, Subscription $subscription)
    {        
        // Only the subscriber can cancel their own subscription
        abort_unless($subscription->subscriber_id === Auth::id(), 403);

        // 1) Update on Stripe
        $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );
        // 2) Mirror locally for instant UX (webhooks remain source of truth)
        $subscription->update(['cancel_at_period_end' => true]);

        return back()->with('success', 'Your subscription will cancel at period end.');
    }

    /**
     * Cancel immediately (no more access now).
     * - You can choose proration behavior; below shows invoice_now + prorate for fairness.
     * - If you don’t want to prorate, set 'prorate' => false and omit 'invoice_now'.
     */
    public function cancelNow(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->subscriber_id === Auth::id(), 403);

        // 1) Cancel on Stripe immediately
        // NOTE: As of current Stripe PHP SDK, `cancel()` immediately ends the subscription.
        // Optional params for proration & invoicing:
        $this->stripe->subscriptions->cancel(
            $subscription->stripe_subscription_id,
            [
                // Uncomment / tweak based on your policy:
                // 'invoice_now' => true,
                // 'prorate'     => true,
            ]
        );

        // 2) Mirror locally (webhook `customer.subscription.deleted` will also update)
        $subscription->update([
            'status'               => 'canceled',
            'cancel_at_period_end' => false,
            'current_period_end'   => now(), // optional: immediate effect for UI
        ]);

        return back()->with('success', 'Your subscription has been canceled immediately.');
    }

    /**
     * Resume a subscription that was set to cancel at period end.
     * (Only works before Stripe ends it.)
     */
    public function resume(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->subscriber_id === Auth::id(), 403);

        // If it’s already ended on Stripe, this will fail; that’s expected.
        $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => false]
        );

        $subscription->update(['cancel_at_period_end' => false]);

        return back()->with('success', 'Your subscription will continue past the current period.');
    }

}

