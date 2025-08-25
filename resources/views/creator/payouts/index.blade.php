@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Creator Payouts Index</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
  <div class="row">
    <div class="col-md-8 offset-md-2">

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Payout Settings</h5>
        </div>
        <div class="card-body">
          <p class="mb-2">
            <strong>Status:</strong>
            @if($user->stripe_account_id)
              <span class="badge bg-success">Connected</span>
            @else
              <span class="badge bg-secondary">Not connected</span>
            @endif
          </p>

          @if($account)
            <ul class="list-unstyled small">
              <li>Charges enabled: {{ $account->charges_enabled ? 'Yes' : 'No' }}</li>
              <li>Payouts enabled: {{ $account->payouts_enabled ? 'Yes' : 'No' }}</li>
              <li>Default currency: {{ strtoupper($account->default_currency ?? '—') }}</li>
            </ul>
          @endif

          @if(!$user->stripe_account_id || !($user->stripe_details_submitted))
            <form method="POST" action="{{ route('creator.payouts.connect') }}" class="d-inline">
              @csrf
              <button class="btn btn-primary">Connect / Finish Setup</button>
            </form>
          @endif

          @if($user->stripe_account_id)
            <a href="{{ route('creator.payouts.dashboard') }}" class="btn btn-outline-primary">Open Payout Dashboard</a>

            <form method="POST" action="{{ route('creator.payouts.disconnect') }}" class="d-inline ms-2"
                  onsubmit="return confirm('Disconnect your payout account?');">
              @csrf
              <button class="btn btn-outline-danger">Disconnect</button>
            </form>
          @endif
        </div>
      </div>

    </div>
  </div>
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection