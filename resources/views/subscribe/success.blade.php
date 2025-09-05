@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Subscribe Success Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

@include('partials.flash')

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto text-center">
            <h2 class="mb-3">You’re in! 🎉</h2>
            <p class="text-muted">Your subscription is being finalized. We’ll sync your status shortly.</p>

            <a href="{{ route('me.subscriptions') }}" class="btn btn-primary mt-3">
                View My Subscriptions
            </a>
        </div>
    </div>
</div>



 
            </div>
        </div>
      </div>
    </div>
@endsection