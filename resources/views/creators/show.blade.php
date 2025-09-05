@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Creator page listing plans (</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

@include('partials.flash') {{-- surfaces session toasts/notices --}}

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">

            {{-- Creator header --}}
            <div class="d-flex align-items-center mb-4">
                <img src="{{ $creator->profile?->getFirstMediaUrl('avatar') ?: 'https://placehold.co/80x80' }}"
                     class="rounded-circle me-3" width="64" height="64" alt="Avatar">
                <div>
                    <h2 class="mb-0">{{ $creator->profile->display_name ?? $creator->name }}</h2>
                    <div class="text-muted">@{{ $creator->username }}</div>
                </div>
            </div>

            {{-- Onboarding status hint (optional) --}}
            @if(!$creator->profile?->stripe_account_id || !$creator->profile?->stripe_charges_enabled)
                <div class="alert alert-warning">
                    This creator hasn’t finished payments onboarding yet. Subscriptions are currently unavailable.
                </div>
            @endif

            {{-- Plans --}}
            <h4 class="mt-4 mb-3">Subscription Plans</h4>

            @forelse($creator->creatorPlans as $plan)
                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            <div class="text-muted small">
                                ${{ number_format($plan->amount / 100, 2) }} / {{ $plan->interval }}
                            </div>
                        </div>

                        @auth
                            @if(auth()->id() === (int)$creator->id)
                                <span class="badge bg-secondary">You’re the creator</span>
                            @elseif(!$creator->profile?->stripe_account_id || !$creator->profile?->stripe_charges_enabled)
                                <button class="btn btn-secondary" disabled>Unavailable</button>
                            @else
                                <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        Subscribe
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary">
                                Log in to subscribe
                            </a>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="alert alert-info">No plans yet.</div>
            @endforelse

        </div>
    </div>
</div>


 
            </div>
        </div>
      </div>
    </div>
@endsection