@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">{{ $post->title }}</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			
@if($post->visibility === 'public')
    @if($post->media_type === 'image')
        @if($post->hasMedia('images'))
        <div>
            <img src="{{ $post->getFirstMediaUrl('images') }}"
                alt="Post Image"
                class="img-fluid rounded">
        </div>
        @endif
    @endif

    @if($post->media_type === 'video')
        @if($post->hasMedia('videos'))


    @php $media = $post->getFirstMedia('videos'); @endphp

    @if($media)
    <video
        controls
        preload="metadata"
        style="max-width:100%;height:auto"
        {{-- Optional poster if you have a thumbnail collection --}}
        {{-- poster="{{ optional($post->getFirstMedia('thumbnails'))->getUrl() }}" --}}
    >
        <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
        Your browser does not support the video tag.
    </video>
    @else
    <p class="text-muted">No video attached to this post.</p>
    @endif


        @endif
    @endif

    
@elseif(($post->visibility === 'subscribers' || $post->visibility === 'paid') && $isSubscribed)
    @if($post->media_type === 'image')
        @if($post->hasMedia('images'))
        <div>
            <img src="{{ $post->getFirstMediaUrl('images') }}"
                alt="Post Image"
                class="img-fluid rounded">
        </div>
        @endif
    @endif

    @if($post->media_type === 'video')
        @if($post->hasMedia('videos'))


    @php $media = $post->getFirstMedia('videos'); @endphp

    @if($media)
    <video
        controls
        preload="metadata"
        style="max-width:100%;height:auto"
        {{-- Optional poster if you have a thumbnail collection --}}
        {{-- poster="{{ optional($post->getFirstMedia('thumbnails'))->getUrl() }}" --}}
    >
        <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
        Your browser does not support the video tag.
    </video>
    @else
    <p class="text-muted">No video attached to this post.</p>
    @endif


        @endif
    @endif

@elseif(($post->visibility === 'subscribers' || $post->visibility === 'paid') && !$isSubscribed)
    <div>
        <img src="{{ asset('images/placeholders/media-locked.png') }}">
        <p style="color: red;">SUBSCRIPTION NEEDED TO UNLOCK MEDIA</p>
    </div>
@endif

<div>
	<p>
        {{ $post->body }}
    </p>
</div>

 
            </div>
        </div>
      </div>
    </div>
@endsection