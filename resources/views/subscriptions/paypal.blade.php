@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">{{ $profile->display_name }}'s Public Profile</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="mb-1">{{ $plan->name }}</h3>

                    <div class="text-muted mb-3">
                        {{ $plan->currency }} {{ number_format($plan->amount, 2) }}
                        @if($plan->interval_count == 1)
                            per {{ strtolower($plan->interval_unit) }}
                        @else
                            every {{ $plan->interval_count }} {{ strtolower($plan->interval_unit) }}s
                        @endif
                    </div>

                    <div id="paypal-button-container" class="my-3"></div>

                    <div class="small text-muted">
                        After approval, we’ll verify your subscription and redirect you.
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('creator.page', $plan->creator->username) }}" class="btn btn-outline-secondary btn-sm">
                    ← Back to @{{ $plan->creator->username }}
                </a>
            </div>
        </div>
    </div>
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection

@push('scripts')
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&vault=true&intent=subscription"></script>

    <script>
        // Tiny toast helper
        const toast = (icon, title, text = '') => {
            Swal.fire({
                toast: true,
                icon,
                title,
                text,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2600
            });
        };

        // Flash toasts from session (e.g., after cancel/switch)
        @if(session('success')) toast('success', @json(session('success'))); @endif
        @if(session('error'))   toast('error',   @json(session('error')));   @endif

        document.addEventListener('DOMContentLoaded', function () {
            // Defensive: ensure SDK loaded
            if (typeof paypal === 'undefined' || !paypal.Buttons) {
                toast('error', 'PayPal SDK failed to load', 'Check your client id or network.');
                return;
            }

            paypal.Buttons({
                style: { label: 'subscribe', shape: 'pill' },

                // Create the PayPal subscription for this plan
                createSubscription: function (data, actions) {
                    return actions.subscription.create({
                        plan_id: @json($planId) // e.g. "P-XXXX"
                    });
                },

                // After buyer approves in PayPal
                onApprove: function (data) {
                    // Send subscriptionID to server for verification & local persistence
                    fetch(@json(route('paypal.subscribe.verify')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            subscription_id: data.subscriptionID, // e.g. "I-XXXX"
                            plan_id: @json($planId),
                            creator_id: @json($creatorId),
                        })
                    })
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(j => {
                        if (j.ok) {
                            toast('success', 'Subscribed!', 'Welcome aboard 🎉');
                            setTimeout(() => window.location = @json(url('/dashboard')), 800);
                        } else {
                            toast('error', 'Verification failed', 'Please try again.');
                        }
                    })
                    .catch(() => {
                        toast('error', 'Network or server error', 'Please try again.');
                    });
                },

                onError: function (err) {
                    console.error('PayPal error:', err);
                    toast('error', 'PayPal error', 'Please try again or use a different method.');
                }
            }).render('#paypal-button-container');
        });
    </script>
@endpush
