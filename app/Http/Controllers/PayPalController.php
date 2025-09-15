<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\Subscription;
use App\Services\PayPalClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PayPalController extends Controller
{
    public function __construct(private PayPalClient $paypal) {}

    /**
     * Creator landing page: /@{username}
     * Shows creator profile + active plans.
     */
    public function showCreator(string $username)
    {
        /** @var \App\Models\User $creator */
        $creator = User::query()->where('name', $username)->firstOrFail();
        $profile = $creator->profile;

        $plans = CreatorPlan::query()
            ->where('creator_id', $creator->id)
            ->where('active', true)
            ->orderBy('amount')
            ->get();

        // Get full URL to avatar
        $avatarUrl = $profile->getFirstMediaUrl('avatar');
        // Get full URL to banner
        $bannerUrl = $profile->getFirstMediaUrl('banner');


        return view('creator.show', compact('creator', 'plans', 'profile', 'avatarUrl', 'bannerUrl'));
    }

    /**
     * Render PayPal subscribe page for a specific plan (route model bound by paypal_plan_id).
     * Route example:
     *   Route::get('/subscribe/{plan:paypal_plan_id}', [PayPalController::class, 'show'])->name('paypal.subscribe.show');
     */
    public function show(CreatorPlan $plan)
    {
        // Prevent creators from subscribing to themselves
        if (Auth::id() === (int) $plan->creator_id) {
            abort(403, 'You cannot subscribe to your own plan.');
        }

        if (!$plan->active) {
            abort(404, 'Plan is inactive.');
        }

        return view('subscriptions.paypal', [
            'paypalClientId' => config('paypal.client_id'),
            'plan'           => $plan,
            'planId'         => $plan->paypal_plan_id,
            'creatorId'      => $plan->creator_id,
        ]);
    }

    /**
     * Verify an approved PayPal subscription from the client after onApprove().
     * Expects JSON:
     *   { subscription_id: "I-XXXX", plan_id: "P-XXXX", creator_id: <int> }
     * Returns JSON: { ok: true, status: "ACTIVE" }
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'subscription_id' => 'required|string',
            'plan_id'         => 'required|string',
            'creator_id'      => 'required|integer',
        ]);

        // Look up the plan (must exist + be active)
        $plan = CreatorPlan::query()
            ->where('paypal_plan_id', $data['plan_id'])
            ->where('active', true)
            ->firstOrFail();

        // Sanity: creator_id in request must match DB
        if ((int) $plan->creator_id !== (int) $data['creator_id']) {
            throw ValidationException::withMessages([
                'creator_id' => 'Creator/plan mismatch.',
            ]);
        }

        // Prevent self-subscription
        if ((int) $plan->creator_id === (int) Auth::id()) {
            throw ValidationException::withMessages([
                'subscription' => 'You cannot subscribe to your own plan.',
            ]);
        }

        // Fetch PayPal subscription details
        $details = $this->paypal->getSubscription($data['subscription_id']);
        $status  = (string) ($details['status'] ?? 'APPROVAL_PENDING');

        // Acceptable initial states upon approval
        $allowed = ['ACTIVE', 'APPROVAL_PENDING', 'APPROVED'];
        if (!in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'subscription' => "Subscription not in a valid state ({$status}).",
            ]);
        }

        // Derive a reasonable start timestamp if PayPal provided one
        $startsAt = null;
        if (!empty($details['start_time'])) {
            $startsAt = Carbon::parse($details['start_time']);
        } elseif (!empty($details['create_time'])) {
            $startsAt = Carbon::parse($details['create_time']);
        } else {
            $startsAt = now();
        }

        // Idempotent upsert keyed by PayPal subscription id (I-XXXX)
        $sub = Subscription::updateOrCreate(
            ['provider_subscription_id' => $data['subscription_id']],
            [
                'user_id'           => Auth::id(),
                'creator_id'        => $plan->creator_id,
                'provider'          => 'paypal',
                'provider_plan_id'  => $plan->paypal_plan_id,
                'status'            => $status,
                'starts_at'         => $startsAt,
                'meta'              => $details,
            ]
        );

        return response()->json([
            'ok'     => true,
            'status' => $sub->status,
        ]);
    }

    /**
     * Cancel a subscription (fan-initiated).
     * Form posts: subscription_id = "I-XXXX"
     */
    public function cancel(Request $request)
    {
        $data = $request->validate([
            'subscription_id' => 'required|string',
        ]);

        // Ensure this sub belongs to the current user
        $sub = Subscription::query()
            ->where('provider', 'paypal')
            ->where('provider_subscription_id', $data['subscription_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Call PayPal to cancel, then mark locally
        $this->paypal->cancelSubscription($data['subscription_id'], 'User requested cancellation.');

        $sub->update([
            'status'  => 'CANCELLED',
            'ends_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled.');
    }

    /**
     * OPTIONAL: Switch plans (revise) within the same creator.
     * Expects: subscription_id (I-XXXX), new_plan_id (P-XXXX)
     */
    public function switchPlan(Request $request)
    {
        $data = $request->validate([
            'subscription_id' => 'required|string',
            'new_plan_id'     => 'required|string',
        ]);

        $sub = Subscription::query()
            ->where('provider', 'paypal')
            ->where('provider_subscription_id', $data['subscription_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // New plan must belong to the same creator and be active
        $newPlan = CreatorPlan::query()
            ->where('paypal_plan_id', $data['new_plan_id'])
            ->where('creator_id', $sub->creator_id)
            ->where('active', true)
            ->firstOrFail();

        // PayPal "revise" call (not exposed in PayPalClient above; inline here)
        $res = $this->paypal->http()->post("v1/billing/subscriptions/{$data['subscription_id']}/revise", [
            'headers' => $this->paypal->headers(),
            'json' => [
                'plan_id' => $newPlan->paypal_plan_id,
                // You can add proration/plan change preferences here if needed
            ],
        ]);

        // If OK, update local subscription plan id
        if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 300) {
            $sub->update(['provider_plan_id' => $newPlan->paypal_plan_id]);
            return back()->with('success', 'Plan switched successfully.');
        }

        throw ValidationException::withMessages([
            'subscription' => 'Failed to switch plans.',
        ]);
    }     

}

