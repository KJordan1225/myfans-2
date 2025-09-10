@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Edit User Profile') }}
    </h2>
@endsection

@section('content')
<div class="row">
    @include('layouts.components.sidebar')

    <div class="col-md-9">
        <h3 class="my-3">Edit User Profile</h3>
        <hr />

        <div class="row mt-2">
            <div class="col-md-12">
                <div class="max-w-xl mx-auto p-6 rounded shadow">
                    <h2 class="text-2xl font-bold mb-4 dark:text-gray-100">Edit Your Profile</h2>

                    {{-- Success / error flash --}}
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- FORM --}}
                    <form action="{{ route('user-profile.update', $profile) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Display Name --}}
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input
                                type="text"
                                id="display_name"
                                name="display_name"
                                class="form-control @error('display_name') is-invalid @enderror"
                                style="border: 2px solid #6f42c1;"
                                value="{{ old('display_name', $profile->display_name ?? '') }}"
                                required
                            >
                            @error('display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bio --}}
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea
                                id="bio"
                                name="bio"
                                rows="4"
                                class="form-control @error('bio') is-invalid @enderror"
                                style="border: 2px solid #6f42c1;"
                            >{{ old('bio', $profile->bio ?? '') }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Avatar --}}
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Avatar</label>
                            @php
                                $avatarUrl = $profile?->getFirstMediaUrl('avatar') ?: asset('images/placeholders/default-avatar.png');
                            @endphp

                            <input
                                type="file"
                                id="avatar"
                                name="avatar"
                                accept="image/*"
                                class="form-control @error('avatar') is-invalid @enderror"
                                onchange="previewImage(this, 'avatar-preview')"
                            >
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="mt-2">
                                <img
                                    id="avatar-preview"
                                    src="{{ $avatarUrl }}"
                                    alt="Avatar Preview"
                                    class="w-24 h-24 rounded-full object-cover border"
                                />
                            </div>
                        </div>

                        {{-- Banner --}}
                        <div class="mb-3">
                            <label for="banner" class="form-label">Banner</label>
                            @php
                                $bannerUrl = $profile?->getFirstMediaUrl('banner') ?: asset('images/placeholders/default-banner.png');
                            @endphp

                            <input
                                type="file"
                                id="banner"
                                name="banner"
                                accept="image/*"
                                class="form-control @error('banner') is-invalid @enderror"
                                onchange="previewImage(this, 'banner-preview')"
                            >
                            @error('banner')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="mt-2">
                                <img
                                    id="banner-preview"
                                    src="{{ $bannerUrl }}"
                                    alt="Banner Preview"
                                    class="w-full h-48 object-cover border rounded"
                                />
                            </div>
                        </div>

                        {{-- Website --}}
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input
                                type="url"
                                id="website"
                                name="website"
                                class="form-control @error('website') is-invalid @enderror"
                                style="border: 2px solid #6f42c1;"
                                value="{{ old('website', $profile->website ?? '') }}"
                                placeholder="https://example.com"
                            >
                            @error('website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Twitter --}}
                        <div class="mb-3">
                            <label for="twitter" class="form-label">Twitter</label>
                            <input
                                type="text"
                                id="twitter"
                                name="twitter"
                                class="form-control @error('twitter') is-invalid @enderror"
                                style="border: 2px solid #6f42c1;"
                                value="{{ old('twitter', $profile->twitter ?? '') }}"
                                placeholder="@handle"
                            >
                            @error('twitter')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Instagram --}}
                        <div class="mb-3">
                            <label for="instagram" class="form-label">Instagram</label>
                            <input
                                type="text"
                                id="instagram"
                                name="instagram"
                                class="form-control @error('instagram') is-invalid @enderror"
                                style="border: 2px solid #6f42c1;"
                                value="{{ old('instagram', $profile->instagram ?? '') }}"
                                placeholder="@handle"
                            >
                            @error('instagram')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Is Creator --}}
                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="is_creator"
                                name="is_creator"
                                value="1"
                                {{ old('is_creator', (int) ($profile->is_creator ?? 0)) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" 
                                    for="is_creator"
                                    style="color: red;
                                            font-weight: bold;
                                            font-size: 22px;">
                                I'm a content creator
                            </label>
                        </div>

                        {{-- Submit --}}
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                Update Profile
                            </button>
                        </div>
                    </form>

                    {{-- Delete (wire to your destroy route) --}}
                    <form
                        action="#"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to delete your profile?');"
                        class="mt-2"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            Delete Profile
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Image preview JS --}}
<script>
function previewImage(input, previewId) {
    const file = input.files?.[0];
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}
</script>
@endsection
