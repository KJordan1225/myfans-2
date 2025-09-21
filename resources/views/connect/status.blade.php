@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Connect Status Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Monetize • Stripe Connect</h1>
        <a href="{{ route('dev.webhooks') }}" class="btn btn-sm btn-outline-secondary">
            View Recent Webhooks
        </a>
    </div>

    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $profile = $user->profile; // assumes relation ->profile
        $acctId = $profile?->stripe_account_id;
        $chargesEnabled = (bool)($profile?->stripe_charges_enabled);
        $payoutsEnabled = (bool)($profile?->stripe_payouts_enabled);
        $needsMore = !($chargesEnabled && $payoutsEnabled);
    @endphp

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <span class="badge text-bg-{{ $chargesEnabled ? 'success' : 'secondary' }}">
                    Charges {{ $chargesEnabled ? 'Enabled' : 'Pending' }}
                </span>
                <span class="badge text-bg-{{ $payoutsEnabled ? 'success' : 'secondary' }}">
                    Payouts {{ $payoutsEnabled ? 'Enabled' : 'Pending' }}
                </span>
                @if($profile?->stripe_requirements_due_at)
                    <span class="badge text-bg-warning">
                        Requirements due {{ $profile->stripe_requirements_due_at->diffForHumans() }}
                    </span>
                @endif
            </div>

            @if(!$acctId)
                <p class="text-muted">
                    You don’t have a Stripe Connect account yet. Start onboarding to accept fan subscriptions
                    and receive payouts directly to your bank.
                </p>                
                <a class="btn btn-primary"
                   href="{{ route('connect.start', $user) }}">
                    Start Stripe Onboarding
                </a>
            @else
                <dl class="row small">
                    <dt class="col-sm-3">Account ID</dt>
                    <dd class="col-sm-9">{{ $acctId }}</dd>

                    <dt class="col-sm-3">Onboarded</dt>
                    <dd class="col-sm-9">
                        {{ $profile?->stripe_onboarded_at ? $profile->stripe_onboarded_at->toDayDateTimeString() : 'Not fully onboarded' }}
                    </dd>
                </dl>

                <div class="d-flex gap-2 flex-wrap">
                    {{-- Refresh flags via API pull --}}
                    <form method="POST" action="{{ route('connect.status', $user) }}">
                        @csrf
                        <button class="btn btn-secondary">Refresh Status</button>
                    </form>

                    {{-- Open Express Dashboard --}}
                    <form method="POST" action="{{ route('connect.dashboard', $user) }}">
                        @csrf
                        <button class="btn btn-outline-primary">Open Stripe Dashboard</button>
                    </form>

                    {{-- Resume onboarding if requirements are pending --}}
                    @if($needsMore)
                        <a class="btn btn-warning" href="{{ route('connect.start', $user) }}">
                            Resume Onboarding
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4">
        <p class="small text-muted mb-1">Tips</p>
        <ul class="small text-muted mb-0">
            <li>Use the “Refresh Status” button after returning from Stripe if you don’t yet see updated flags.</li>
            <li>If Stripe asks for more information later, you can “Resume Onboarding.”</li>
        </ul>
    </div>
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection
