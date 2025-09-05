<?php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\Subscriptions\SyncStripeSubscription;

class CheckoutController extends Controller
{
    public function __construct(
        private StripeConnectService $stripeSvc,
        private SyncStripeSubscription $sync
    ) {}


    public function subscribe(Request $request, CreatorPlan $plan)
    {
        $user = Auth::user();

        abort_if($user->id === (int) $plan->creator_id, 422, 'You cannot subscribe to your own plan.');
        abort_unless($plan->active, 404, 'Plan is inactive.');

        $url = $this->stripeSvc->startCheckout(
            subscriber: $user,
            plan: $plan,
            successUrl: route('subscribe.success'),
            cancelUrl: route('subscribe.cancel')
        );

        return redirect()->away($url);
    }

    public function success(Request $request)
    {
        if ($sid = $request->query('session_id')) {
            // Best-effort local mirror right away (webhook remains source of truth)
            try {
                $this->sync->fromSessionId($sid);
            } catch (\Throwable $e) {
                // Swallow and show page; webhook will fix it soon anyway
                \Log::warning('Sync from success failed', ['e' => $e->getMessage()]);
            }
        }

        return view('subscribe.success');
    }


    public function cancel(Request $request)
    {
        return redirect()->back()->with('info', 'Checkout was canceled.');
    }
}

