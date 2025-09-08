@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Create Post</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
 
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <h5 class="mb-2">Please fix the following:</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create Post</h4>
                </div>
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('creator.posts.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf                        
                        {{-- Title --}}
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="" style="border: 2px solid #6f42c1;" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Body --}}
                        <div class="mb-3">
                            <label for="body" class="form-label">Body</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="5" style="border: 2px solid #6f42c1;"></textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
						
						
						
						{{-- Media Type --}}
						<div class="mb-3">
							<label for="mediaType" class="form-label">Media Upload Type (required)</label>
							<select id="mediaType" name="media_type" class="form-select">
								<option value="">-- Select --</option>
								<option value="image" {{ old('media_type') === 'image' ? 'selected' : '' }}>Image</option>
								<option value="video" {{ old('media_type') === 'video' ? 'selected' : '' }}>Video</option>
							</select>							
						</div>
						

						{{-- Video upload --}}
						<div class="mb-3" id="videoGroup" style="display:none;">
							<label for="video" class="form-label">Upload Video</label>
							<input type="file" id="video" name="video" class="form-control" accept="video/mp4,video/quicktime" disabled>
							<small class="text-muted">Max ~50MB. Accepts mp4 and qucktime.</small>
						</div>

						
						{{-- Image upload --}}
						<div class="mb-3" id="imageGroup" style="display:none;">
							<label for="image" class="form-label">Upload Image</label>
							<input type="file" id="image" name="image" class="form-control" accept="image/*" disabled>
						</div>


                        {{-- Price --}}
                        <!-- <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="" style="border: 2px solid #6f42c1;">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->

                        {{-- Is Paid --}}
                        <!-- <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_paid" name="is_paid" value="1" style="border: 2px solid #6f42c1;">
                            <label class="form-check-label" for="is_paid">This is a paid post</label>
                            @error('is_paid')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div> -->

                        {{-- Visibility --}}
                        <div class="mb-3">
                            <label for="visibility" class="form-label">Visibility</label>
                            <select class="form-select @error('visibility') is-invalid @enderror" id="visibility" name="visibility" style="border: 2px solid #6f42c1;" required>
                                <option value="public">Public</option>
                                <option value="subscribers">Subscribers Only</option>
                                <option value="paid">Paid Only</option>
                            </select>
                            @error('visibility')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Create Post</button>
                            <a href="#" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
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



@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectEl   = document.getElementById('mediaType');
    const imageGroup = document.getElementById('imageGroup');
    const videoGroup = document.getElementById('videoGroup');
    const imageInput = document.getElementById('image');
    const videoInput = document.getElementById('video');

    function toggleVisibility(value) {
        if (value === 'image') {
            imageGroup.style.display = 'block';
            imageInput.disabled = false;
            imageInput.required = true;

            videoGroup.style.display = 'none';
            videoInput.disabled = true;
            videoInput.required = false;
            videoInput.value = '';
        } else if (value === 'video') {
            videoGroup.style.display = 'block';
            videoInput.disabled = false;
            videoInput.required = true;

            imageGroup.style.display = 'none';
            imageInput.disabled = true;
            imageInput.required = false;
            imageInput.value = '';
        } else {
            // Nothing selected
            imageGroup.style.display = 'none';
            videoGroup.style.display = 'none';
            imageInput.disabled = true; imageInput.required = false; imageInput.value = '';
            videoInput.disabled = true; videoInput.required = false; videoInput.value = '';
        }
    }

    // Change handler
    selectEl.addEventListener('change', function () {
        toggleVisibility(this.value);
    });

    // Initialize on load (preserves old selection after validation errors)
    toggleVisibility(selectEl.value);
});
</script>

@endpush