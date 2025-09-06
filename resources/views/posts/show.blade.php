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

    <h1 class="mb-4">
        Posts by {{ $creator->name }}
    </h1>

    @forelse($posts as $post)
        <div class="card mb-4">
            <div class="card-body">
                {{-- Post info --}}
                <h3 class="card-title">{{ $post->title ?? 'Untitled Post' }}</h3>
                @if(!empty($post->body))
                    <p class="card-text">{{ Str::limit($post->body, 600) }}</p>
                @endif

                {{-- Associated images from Spatie --}}
                @if($post->media->isNotEmpty())
                    <div class="row g-3 mt-3">
                        @foreach($post->media as $media)
                            @empty($sub)
                                <div class="col-12 col-sm-6 col-md-4">
                                    <img
                                        src="{{ asset('images/placeholders/media-locked.png') }}" }}"
                                        alt="{{ $media->name }}"
                                        class="img-fluid rounded shadow-sm"
                                    >
                                    <div class="small text-muted mt-1 text-truncate">{{ $media->file_name }}</div>
                                </div>
                            @else
                                <div class="col-12 col-sm-6 col-md-4">
                                    <img
                                        src="{{ $media->getUrl() }}"
                                        alt="{{ $media->name }}"
                                        class="img-fluid rounded shadow-sm"
                                    >
                                    <div class="small text-muted mt-1 text-truncate">{{ $media->file_name }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mt-2 mb-0">No images attached to this post.</p>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">This user hasn’t created any posts yet.</div>
    @endforelse

    {{-- Pagination (Bootstrap 5 renderer) --}}
    @if($posts->hasPages())
        <nav aria-label="Posts pagination" class="d-flex justify-content-center mt-4">
            {{ $posts->onEachSide(1)->links('pagination::bootstrap-5') }}
        </nav>
    @endif

</div>



 
            </div>
        </div>
      </div>
    </div>
@endsection