@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Public Profile</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			
<style>
    .banner-container {
        position: relative; /* Essential for positioning the overlay image */
        width: 100%;
        height: 250px; /* Adjust banner height as needed */
        overflow: hidden; /* Ensures nothing overflows the banner */
        background-color: #f0f0f0; /* Fallback for banner image */
        border-radius: 8px; /* Optional: rounded corners for the banner */
    }

    .banner-img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ensures the image covers the area without distortion */
        display: block;
    }

    .profile-img-overlay {
        position: absolute; /* Positions the profile image relative to its parent (banner-container) */
        bottom: 15px; /* Adjust as needed for vertical position */
        left: 15px; /* Adjust as needed for horizontal position */
        width: 100px; /* Desired width of the circular profile image */
        height: 100px; /* Desired height of the circular profile image */
        border-radius: 50%; /* Makes the image circular */
        border: 4px solid #fff; /* White border around the profile image */
        box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1); /* Optional: subtle shadow */
        object-fit: cover; /* Ensures the image covers the circular area */
        background-color: #ccc; /* Fallback background color */
        z-index: 10; /* Ensures the profile image is on top of the banner */
    }

    /* Optional: Add some padding to the body for better visualization */
    body {
        padding: 20px;
        background-color: #f8f9fa;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- Header: creator identity --}}
            <div class="container mt-5">
                <div class="banner-container">
                    @if ($profile->hasMedia('banner'))
                        <img src="{{ $profile->getFirstMediaUrl('banner') }}" alt="Banner" class="banner-img">
                    @else
                        <img src="https://via.placeholder.com/1200x250/508D69/FFFFFF?text=Your+Banner+Image" alt="Banner Image" class="banner-img">
                    @endif

                     @if ($profile->hasMedia('avatar'))
                        <img src="{{ $profile->getFirstMediaUrl('avatar') }}" alt="Avatar" class="profile-img-overlay">
                    @else
                        <img src="https://via.placeholder.com/100x100/CEDEBD/000000?text=Profile" alt="Profile Image" class="profile-img-overlay">
                    @endif                    
                </div>                
            </div>


            {{-- Plans --}}
            @if($plans->isEmpty())
                <div class="alert alert-info">This creator has no active plans yet.</div>
            @else
                <div class="row g-3">
                    @foreach($plans as $plan)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1">{{ $plan->name }}</h5>

                                    <div class="text-muted mb-2">
                                        {{ $plan->currency }} {{ number_format($plan->amount, 2) }}
                                        @if($plan->interval_count == 1)
                                            / {{ strtolower($plan->interval_unit) }}
                                        @else
                                            • every {{ $plan->interval_count }} {{ strtolower($plan->interval_unit) }}s
                                        @endif
                                    </div>

                                    <p class="small text-muted mb-3">
                                        Full access to subscriber-only posts and updates.
                                    </p>

                                    <div class="mt-auto">
                                        {{-- Link to the PayPal subscribe page for this plan --}}
                                        <a href="{{ route('paypal.subscribe.show', $plan->paypal_plan_id) }}"
                                           class="btn btn-primary w-100">
                                            Subscribe with PayPal
                                        </a>
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
      </div>
    </div>
@endsection