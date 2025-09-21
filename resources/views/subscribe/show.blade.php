@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Fan Subscribe Show</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
  <h1 class="h4 mb-3">Subscribe to {{ $creator->name }}</h1>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  @if($plans->isEmpty())
    <div class="alert alert-info">This creator has no active plans yet.</div>
  @else
    <div class="row g-3">
      @foreach($plans as $plan)
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">{{ $plan->name }}</h5>
              <div class="mb-2">{{ strtoupper($plan->currency) }} {{ number_format($plan->price_cents/100, 2) }} / {{ $plan->interval }}</div>
              <div class="mt-auto">
                <form method="POST" action="{{ route('subscribe.start', $plan) }}">
                  @csrf
                  <button class="btn btn-primary w-100">Subscribe</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection