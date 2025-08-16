@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">{{ $profile->display_name }}'s Public Profile</h3>
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

    .profile-img-card {
		position: absolute; /* Positions the profile image relative to its parent (banner-container) */
		bottom: 1px; /* Adjust as needed for vertical position */
		left: 1px; /* Adjust as needed for horizontal position */
		width: 40px; /* Desired width of the circular profile image */
		height: 40px; /* Desired height of the circular profile image */
		border-radius: 50%; /* Makes the image circular */
		border: 4px solid #fff; /* White border around the profile image */
		box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1); /* Optional: subtle shadow */
		object-fit: cover; /* Ensures the image covers the circular area */
		background-color: #ccc; /* Fallback background color */
		z-index: 10; /* Ensures the profile image is on top of the banner */
	}

</style>


<div class="container mt-1">	
	<div class="banner-container">
		<!-- Banner -->
			@if ($profile->hasMedia('banner'))
				<img src="{{ $profile->getFirstMediaUrl('banner') }}" alt="Banner" class="banner-img">
			@else
				<img src="{{ asset('images/default-banner.jpg') }}" alt="Default Banner" class="banner-img">
			@endif
		<!-- Avatar -->
			@if ($profile->hasMedia('avatar'))
				<img src="{{ $profile->getFirstMediaUrl('avatar') }}" alt="Avatar" class="profile-img-overlay">
			@else
				<img src="{{ asset('images/default-avatar.png') }}" alt="Default Avatar" class="profile-img-overlay">
			@endif
	</div>

	<div>
		<form method="POST" action="{{ route('subscriptions.subscribe', $subscription) }}">
			@csrf
			<button type="submit" class="btn btn-primary">
				Subscribe - {{$subscription->title}}-{{$subscription->price}}-{{$subscription->interval}}
			</button>
		</form>
	</div>

	<div class="mt-4">
		<p>{{ $postCount }} Posts</p>
	</div>



<div class="container my-4">
    @foreach($posts as $item)
        <div class="card mb-4">

            {{-- Card Header --}}
            <div class="card-header">
                {{ $item['title'] ?? 'Uncategorized' }}
            </div>

            {{-- Card Image --}}
            @if ($profile->hasMedia('avatar'))
                <img src="{{ $profile->getFirstMediaUrl('avatar') }}" class="profile-img-card" alt="user avatar">
            @else
                <img src="{{ asset('images/default-avatar.png') }}" class="card-img-top" alt="Default Image">
            @endif

            {{-- Card Body - Main Info --}}
            <div class="card-body">
                <h5 class="card-title">{{ $item['title'] }}</h5>
                <p class="card-text">{{ Str::limit($item['body'], 100) }}</p>
                <a href="#" class="btn btn-primary">Read More</a>
            </div>
        </div>
    @endforeach
</div>



</div> 

 
            </div>
        </div>
      </div>
    </div>
@endsection