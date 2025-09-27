@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Creator Plans Edit</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <h1 class="h4 mb-3">Edit Subscription Plan</h1>

    <form method="POST" action="{{ route('creator.plans.update', $plan->id) }}" class="card card-body shadow-sm">
        @csrf
        @method('PUT') <!-- Add PUT method for updating the resource -->

        <div class="mb-3">
            <label class="form-label">Plan Name</label>
            <input name="name" class="form-control" value="{{ old('name', $plan->name) }}" required maxlength="120">
            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Price (USD)</label>
                <input name="price" type="number" min="1" step="0.01" class="form-control" value="{{ old('price', $plan->price) }}" required>
                @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Interval</label>
                <select name="interval" class="form-select" required>
                    @foreach(['day','week','month','year'] as $i)
                        <option value="{{ $i }}" @selected(old('interval', $plan->interval) === $i)>{{ ucfirst($i) }}</option>
                    @endforeach
                </select>
                @error('interval') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Currency</label>
                <input name="currency" class="form-control" value="{{ old('currency', $plan->currency) }}" maxlength="3" required>
                @error('currency') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-check form-switch my-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Plan is Active</label>
        </div>

        <button class="btn btn-primary">Update Plan</button>
        <a href="{{ route('creator.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

 
            </div>
        </div>
      </div>
    </div>
	
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if($errors->any())
<script>Swal.fire({icon:'error',title:'Please fix the errors',text:'Some fields need your attention.'});</script>
@endif

@endsection
