@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Subscription Cancellation</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div>
	<form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST" class="inline cancel-form">
		@csrf
		<button type="button" class="btn btn-warning btn-sm cancel-btn">Cancel at period end</button>
	</form>

	<form action="{{ route('subscriptions.cancel-now', $subscription) }}" method="POST" class="inline cancel-now-form">
		@csrf
		<button type="button" class="btn btn-danger btn-sm cancel-now-btn">Cancel now</button>
	</form> 
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const wire = (selector, text, formClass) => {
    const btns = document.querySelectorAll(selector);
    btns.forEach(btn => btn.addEventListener('click', () => {
      Swal.fire({
        icon: 'warning',
        title: text,
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
      }).then((r) => {
        if (r.isConfirmed) document.querySelector(formClass).submit();
      });
    }));
  };

  wire('.cancel-btn',     'Cancel at the end of the current period?', '.cancel-form');
  wire('.cancel-now-btn', 'Cancel immediately?',                      '.cancel-now-form');
});
</script>


 
            </div>
        </div>
      </div>
    </div>
@endsection