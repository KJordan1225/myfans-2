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
        protected SubscriptionService $service
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
        $subscriber = $request->user();

        // Optional policy: $this->authorize('cancel', [$subscriber, $subscription]);
        $this->service->cancel($subscriber, $subscription);

        $productAndPrice = $this->createProductAndPrice();

        return back()->with('success', 'Subscription canceled.');
    }

    /**
     * Subscriber action: resume a previously canceled subscription.
     */
    public function resume(Request $request, Subscription $subscription)
    {
        $subscriber = $request->user();

        // Optional policy: $this->authorize('resume', [$subscriber, $subscription]);
        $this->service->resume($subscriber, $subscription, now()->addMonth());

        return back()->with('success', 'Subscription resumed.');
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
}

