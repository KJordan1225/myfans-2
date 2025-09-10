@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Create Plans Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="row">
  <div class="col-xl-7 mx-auto">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h1 class="h4 mb-3">Create a Plan</h1>
        <form method="POST" action="{{ route('plans.store') }}">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Plan Name</label>
              <input type="text" name="name" class="form-control" placeholder="Gold Tier" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Amount (cents)</label>
              <input type="number" name="amount" class="form-control" min="100" step="50" value="999" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Interval</label>
              <select name="interval" class="form-select" required>
                <option value="month" selected>Monthly</option>
                <option value="year">Yearly</option>
                <option value="week">Weekly</option>
                <option value="day">Daily</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Platform Fee %</label>
              <input type="number" name="platform_fee_percent" class="form-control" step="0.01" min="0" max="100" value="15.00">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary">Create Plan</button>
            <a class="btn btn-outline-secondary" href="{{ route('creator.monetize') }}">Back</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Your Plans</h2>
        @forelse($plans as $plan)
          <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-2">
            <div>
              <div class="fw-semibold">{{ $plan->name }}</div>
              <div class="text-muted small">
                ${{ number_format($plan->amount/100, 2) }} / {{ $plan->interval }} • Fee {{ $plan->platform_fee_percent }}%
              </div>
              <div class="small">Stripe Price: {{ $plan->stripe_price_id }}</div>
            </div>

          </div>
        @empty
          <p class="text-muted mb-0">No plans yet.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>


 
            </div>
        </div>
      </div>
    </div>
@endsection