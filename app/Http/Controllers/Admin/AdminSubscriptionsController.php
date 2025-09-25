<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription; // your model

class AdminSubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string)$request->input('q'));
        $status  = $request->input('status');
        $perPage = (int) $request->input('perPage', 15);

        $subs = Subscription::query()
            ->with(['user','creator','plan'])
            ->when($q, function ($qb) use ($q) {
                $qb->where('id', $q)
                   ->orWhereHas('user', fn($u)=>$u->where('email','like',"%{$q}%")
                                                  ->orWhere('name','like',"%{$q}%"))
                   ->orWhereHas('creator', fn($c)=>$c->where('name','like',"%{$q}%"))
                   ->orWhereHas('plan', fn($p)=>$p->where('name','like',"%{$q}%"));
            })
            ->when($status, fn($qb)=>$qb->where('status',$status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.subscriptions', compact('subs'));
    }

    /**
     * Immediately cancel a Stripe subscription (stops future billing).
     * Works for platform- or Connect-owned subscriptions.
     */
    public function cancel(Subscription $subscription, StripeClient $stripe)
    {
        // 1) Validate we have a Stripe subscription id
        $stripeSubId = $subscription->stripe_subscription_id ?? null;
        if (!$stripeSubId) {
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'Missing Stripe subscription id',
                'text'  => 'This record has no stripe_subscription_id.',
            ]);
        }

        // 2) Determine if this subscription lives on a CONNECTED account
        $creator        = $subscription->creator;                          // relation on your model
        $stripeAccount  = optional($creator?->profile)->stripe_account_id; // e.g., acct_...

        try {
            // 3) Hit Stripe API (platform vs connected account)
            $opts = [];
            if ($stripeAccount) {
                $opts['stripe_account'] = $stripeAccount;
            }

            // Cancel immediately (prevents future invoices). This does not refund prior charges.
            $stripe->subscriptions->cancel($stripeSubId, [], $opts);

            // 4) Update local record
            $subscription->status = 'canceled';
            // If you track these:
            if ($subscription->isFillable('ended_at')) {
                $subscription->ended_at = now();
            }
            if ($subscription->isFillable('current_period_end') && empty($subscription->current_period_end)) {
                $subscription->current_period_end = now();
            }
            $subscription->save();

            return back()->with('swal', [
                'icon'  => 'success',
                'title' => 'Subscription cancelled',
                'text'  => "Stripe subscription {$stripeSubId} has been cancelled.",
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return back()->with('swal', [
                'icon'  => 'error',
                'title' => 'Stripe error',
                'text'  => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('swal', [
                'icon'  => 'error',
                'title' => 'Unexpected error',
                'text'  => $e->getMessage(),
            ]);
        }
    }

}
