@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">My Subscriptions</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
  <h1 class="h4 mb-3">My Subscriptions</h1>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  @forelse($subs as $sub)
    <div class="card mb-3">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">{{ $sub->creator->name ?? 'Creator' }} — {{ $sub->plan->name ?? '' }}</div>
          <div class="small text-muted">
            Status: {{ $sub->status }}
            @if($sub->cancel_at_period_end)
              (cancels on {{ optional($sub->current_period_end)->toFormattedDateString() }})
            @endif
          </div>
        </div>
        <div class="d-flex gap-2">
          @unless($sub->cancel_at_period_end)
            <form method="POST" action="{{ route('subscriptions.cancel', $sub) }}">
              @csrf
              <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel at period end?')">Cancel</button>
            </form>
          @endunless
        </div>
      </div>
    </div>
  @empty
    <div class="alert alert-info">You have no subscriptions yet.</div>
  @endforelse
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection