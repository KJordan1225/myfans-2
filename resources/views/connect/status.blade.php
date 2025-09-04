@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Creator Monetize Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

@php $profile = auth()->user()->profile; @endphp

<div class="row">
  <div class="col-lg-8 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Monetize</h1>
        <p class="mb-2">Connect your Stripe account to receive subscription payouts.</p>

        <ul class="list-group mb-3">
          <li class="list-group-item d-flex justify-content-between">
            <span>Stripe Account ID</span>
            <span class="fw-semibold">{{ $profile?->stripe_account_id ?? '—' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Charges Enabled</span>
            <span class="fw-semibold {{ $profile?->charges_enabled ? 'text-success' : 'text-danger' }}">
              {{ $profile?->charges_enabled ? 'Yes' : 'No' }}
            </span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span>Details Submitted</span>
            <span class="fw-semibold {{ $profile?->details_submitted ? 'text-success' : 'text-danger' }}">
              {{ $profile?->details_submitted ? 'Yes' : 'No' }}
            </span>
          </li>
        </ul>

        <div class="d-flex gap-2">
          <a href="{{ route('connect.start') }}" class="btn btn-primary">Start / Continue Onboarding</a>
          <a href="{{ route('connect.refresh') }}" class="btn btn-outline-secondary">Refresh Status</a>
          <a href="{{ route('plans.create.form') }}" class="btn btn-success {{ $profile?->charges_enabled ? '' : 'disabled' }}">Create Plans</a>
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