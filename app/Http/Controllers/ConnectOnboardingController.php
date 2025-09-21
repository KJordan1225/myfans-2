<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ConnectService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ConnectOnboardingController extends Controller
{
    public function __construct(private readonly ConnectService $connect)
    {
        // Require auth for all actions
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Start (or resume) Stripe Connect Express onboarding.
     * Redirects the creator to Stripe's hosted flow.
     */
    public function start(Request $request, User $creator): RedirectResponse
    {
        $this->authorizeUpdate($creator);

        try {
            $url = $this->connect->createOnboardingLink($creator);
            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('connect.onboarding.start.failed', [
                'creator_id' => $creator->id,
                'err'        => $e->getMessage(),
            ]);

            return back()->with('error', 'Could not start Stripe onboarding. Please try again in a moment.');
        }
    }

    /**
     * Return URL after the Stripe onboarding flow.
     * We pull the latest account status and inform the user.
     */
    public function return(Request $request, User $creator): RedirectResponse
    {
        $this->authorizeUpdate($creator);

        try {
            $this->connect->syncAccountStatus($creator);
            $profile = $creator->profile?->fresh();

            if ($profile && $profile->stripe_charges_enabled && $profile->stripe_payouts_enabled) {
                return redirect()
                    ->route('creator.monetize')
                    ->with('success', 'Stripe Connect onboarding complete! You can now receive subscription payouts.');
            }

            return redirect()
                ->route('creator.monetize')
                ->with('warning', 'Thanks! Stripe still needs more info. Click “Resume Onboarding” to finish.');
        } catch (\Throwable $e) {
            Log::warning('connect.onboarding.return.sync_failed', [
                'creator_id' => $creator->id,
                'err'        => $e->getMessage(),
            ]);

            return redirect()
                ->route('creator.monetize')
                ->with('warning', 'We could not refresh your Stripe status. Please try “Refresh Status.”');
        }
    }

    /**
     * Create an Express Dashboard login link and redirect creator there.
     */
    public function dashboard(Request $request, User $creator): RedirectResponse
    {
        $this->authorizeUpdate($creator);

        try {
            $url = $this->connect->createDashboardLink($creator);
            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('connect.dashboard.link.failed', [
                'creator_id' => $creator->id,
                'err'        => $e->getMessage(),
            ]);

            return back()->with('error', 'Could not open Stripe Dashboard. Please try again.');
        }
    }

    /**
     * Manually refresh local flags by pulling from Stripe Accounts API.
     * Useful if a webhook was missed/delayed.
     */
    public function status(Request $request, User $creator): RedirectResponse
    {
        $this->authorizeView($creator);

        try {
            $this->connect->syncAccountStatus($creator);
            return back()->with('success', 'Stripe account status refreshed.');
        } catch (\Throwable $e) {
            Log::warning('connect.status.refresh.failed', [
                'creator_id' => $creator->id,
                'err'        => $e->getMessage(),
            ]);

            return back()->with('warning', 'Could not refresh Stripe status right now. Please try again.');
        }
    }

    /**
     * Centralized authorization helpers (cleaner methods).
     */
    protected function authorizeUpdate(User $creator): void
    {
        if (! Gate::allows('update', $creator)) {
            throw new AuthorizationException();
        }
    }

    protected function authorizeView(User $creator): void
    {
        if (! Gate::allows('view', $creator)) {
            throw new AuthorizationException();
        }
    }
}
