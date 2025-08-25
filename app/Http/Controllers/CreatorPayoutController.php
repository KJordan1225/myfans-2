<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StripeConnectService;

class CreatorPayoutController extends Controller
{
    public function __construct(private StripeConnectService $connect) {}

    public function index(Request $request)
    {       
        $user = $request->user();
        $profile = $user->profile;
        abort_unless($profile->is_creator, 403);
        $acct = $this->connect->fetchAccount($profile);

        return view('creator.payouts.index', [
            'user'    => $profile,
            'account' => $acct,
        ]);
    }

    public function connect(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        abort_unless($profile->is_creator, 403);

        $url = $this->connect->onboardingLink($profile);
        return redirect()->away($url);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        abort_unless($profile->is_creator && $profile->stripe_account_id, 403);

        return redirect()->away($this->connect->dashboardLoginUrl($profile));
    }

    public function return(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        if ($profile?->stripe_account_id) {
            if ($acct = $this->connect->fetchAccount($profile)) {
                $this->syncUserFromAccount($profile, $acct);
            }
        }
        return redirect()->route('creator.payouts.index')->with('success', 'Payout account updated.');
    }

    public function refresh(Request $request)
    {
        return $this->connect($request);
    }

    public function disconnect(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        abort_unless($profile->is_creator, 403);

        $profile->forceFill([
            'stripe_account_id'        => null,
            'stripe_charges_enabled'   => false,
            'stripe_payouts_enabled'   => false,
            'stripe_details_submitted' => false,
            'stripe_default_currency'  => null,
            'stripe_requirements'      => null,
        ])->save();

        return back()->with('success', 'Disconnected your payout account.');
    }

    private function syncUserFromAccount($profile, $acct): void
    {
        $profile->forceFill([
            'stripe_charges_enabled'   => (bool)($acct->charges_enabled ?? false),
            'stripe_payouts_enabled'   => (bool)($acct->payouts_enabled ?? false),
            'stripe_details_submitted' => (bool)($acct->details_submitted ?? false),
            'stripe_default_currency'  => $acct->default_currency ?? null,
            'stripe_requirements'      => $acct->requirements ? $acct->requirements->toArray() : null,
        ])->save();
    }
}
