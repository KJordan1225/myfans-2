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

	<div class="mt-4">
		<p>{{ $postCount }} Posts</p>
	</div>
</div> 

 
            </div>
        </div>
      </div>
    </div>
@endsection