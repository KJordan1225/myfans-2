@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Subscription Index Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

@include('partials.flash')

<div class="container py-4">
    <div class="row">
        <div class="col-md-9">
            <h3 class="mb-3">My Subscriptions</h3>

            @php
                $subs = \App\Models\Subscription::with(['creator.profile', 'plan'])
                    ->where('subscriber_id', auth()->id())
                    ->latest()->get();
            @endphp

            @if($subs->isEmpty())
                <div class="alert alert-info">You don’t have any active subscriptions yet.</div>
            @else
                @foreach($subs as $sub)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">
                                    <a href="{{ route('creators.show', ['username' => $sub->creator->name]) }}">
                                        {{ $sub->creator->name }}
                                    </a> — {{ $sub->plan->name }}
                                </div>
                                <div class="text-muted small">
                                    Status: {{ $sub->status }}
                                    @if($sub->cancel_at_period_end)
                                        • Cancels at period end
                                    @endif
                                    @if($sub->current_period_end)
                                        • Renews {{ $sub->current_period_end->toFormattedDateString() }}
                                    @endif
                                </div>
                            </div>

                            @if(!$sub->cancel_at_period_end)
                                <form action="{{ route('subscriptions.cancel', $sub) }}" method="POST"
                                      onsubmit="return confirm('Cancel at period end?');">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm">Cancel</button>
                                </form>
                            @else
                                <span class="badge bg-secondary">Queued to cancel</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection