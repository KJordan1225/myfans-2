@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8 text-center">
      <h1 class="display-4 text-danger">403</h1>
      <h2 class="mb-3">Forbidden</h2>
      <p class="lead text-muted">
        Sorry, you don’t have permission/role to access this page.
      </p>

      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary mt-3">Go Back</a>
      <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
    </div>
  </div>
</div>

{{-- Optional SweetAlert2 Popup --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  Swal.fire({
    icon: 'error',
    title: 'Access Denied',
    text: 'You do not have permission/role to view this page.',
    confirmButtonText: 'Ok',
  });
});
</script>
@endsection
