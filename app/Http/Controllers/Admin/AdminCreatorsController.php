<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class AdminCreatorsController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string)$request->input('q'));
        $status  = $request->input('connect'); // onboarded / not-onboarded / any
        $perPage = (int)($request->input('perPage', 12));

        // Define who counts as "creator" for your app:
        // Option 1: users with role 'creator'
        // Option 2: users who have a profile at all
        $query = User::query()
            ->with(['roles', 'profile.media'])
            ->whereHas('roles', fn($r) => $r->where('name', 'creator'))
            ->when($q, function ($qBuilder) use ($q) {
                $qBuilder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%");
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->whereHas('profile', function ($sub) use ($status) {
                    if ($status === 'onboarded') {
                        $sub->whereNotNull('stripe_account_id')
                            ->whereNotNull('stripe_onboarded_at');
                    } elseif ($status === 'not-onboarded') {
                        $sub->where(function ($q) {
                            $q->whereNull('stripe_account_id')
                              ->orWhereNull('stripe_onboarded_at');
                        });
                    }
                });
            })
            ->latest('id');

        $creators = $query->paginate($perPage)->withQueryString();

        return view('admin.creators.index', compact('creators', 'q', 'status', 'perPage'));
    }

    public function resendOnboarding(User $user, StripeClient $stripe)
    {
        $profile = $user->profile;
        if (!$profile || !$profile->stripe_account_id) {
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'No Stripe account yet',
                'text'  => 'This creator does not have a Stripe Connect account ID.',
            ]);
        }

        // Create a new Account Link for onboarding
        // https://stripe.com/docs/api/account_links/create
        $accountId = $profile->stripe_account_id;

        try {
            $link = $stripe->accountLinks->create([
                'account'     => $accountId,
                'refresh_url' => route('admin.creators.index'),
                'return_url'  => route('admin.creators.index'),
                'type'        => 'account_onboarding',
            ]);

            // Option A: Show link in a SweetAlert for copy/open.
            return back()->with('swal', [
                'icon'  => 'info',
                'title' => 'Onboarding link created',
                'html'  => '<p>Send this to the creator:</p><p><a href="'.$link->url.'" target="_blank">'.$link->url.'</a></p>',
            ]);

            // Option B: Email to the creator (implement Mailable/Notification instead).
        } catch (\Throwable $e) {
            return back()->with('swal', [
                'icon'  => 'error',
                'title' => 'Stripe error',
                'text'  => $e->getMessage(),
            ]);
        }
    }
}

