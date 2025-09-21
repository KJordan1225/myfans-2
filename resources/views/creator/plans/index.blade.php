@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Creator Plans Index</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">My Plans</h1>
        <a href="{{ route('creator.plans.create') }}" class="btn btn-primary">New Plan</a>
    </div>

    @if($plans->isEmpty())
        <div class="alert alert-info">
            You don’t have any plans yet. Create at least one plan so fans can subscribe.
        </div>
    @endif

    <div class="list-group">
        @foreach($plans as $plan)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">{{ $plan->name }}</div>
                    <div class="small text-muted">{{ $plan->price_for_humans }}</div>
                    <div class="small">{{ $plan->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('creator.plans.edit', $plan) }}">Edit</a>
                    <form method="POST" action="{{ route('creator.plans.destroy', $plan) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this plan?')">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
 
            </div>
        </div>
      </div>
    </div>
	
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>Swal.fire({icon:'success',title:'Success',text:@json(session('success')),timer:2200,showConfirmButton:false});</script>
@endif
@if(session('needs_plan'))
<script>Swal.fire({icon:'info',title:'Create your first plan',text:'To start earning from followers, create at least one subscription plan.',confirmButtonText:'Create a plan'}).then(()=>{window.location='{{ route('creator.plans.create') }}'});</script>
@endif

@endsection