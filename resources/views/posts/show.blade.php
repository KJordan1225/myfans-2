@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Show Posts</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <div class="mb-4">
        <h2 class="mb-0">{{ $creator->profile->display_name ?? $creator->name }}</h2>
        <div class="text-muted">Viewing posts by {{ $creator->name }}</div>
    </div>

    @forelse($posts as $post)
        <div class="card mb-3">
            <div class="card-body d-flex gap-3">
                {{-- Image (uses eager-loaded media via accessors) --}}
                @php
                    $img = $post->image_thumb_url; // uses accessor above
                @endphp

                <div style="width:160px; flex:0 0 160px;">
                    @if($img)
                        <img src="{{ $img }}" alt="Post image" class="img-fluid rounded" />
                    @else
                        <img src="https://placehold.co/400x300?text=No+Image" alt="No image" class="img-fluid rounded" />
                    @endif
                </div>

                <div class="flex-grow-1">
                    <h5 class="mb-1">{{ $post->title }}</h5>
                    <div class="text-muted small mb-2">#{{ $post->id }}</div>
                    <p class="mb-0">{{ Str::limit($post->body, 220) }}</p>
                </div>
            </div>		
        </div>
    @empty
        <div class="alert alert-info">No posts yet.</div>
    @endforelse	
</div>


 
            </div>
        </div>
      </div>
    </div>
@endsection