@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h4>Subscribe to {{ $creator->display_name }}</h4>

  <form method="POST" action="{{ route('creators.subscribe.checkout', $creator->id) }}">
    @csrf

    <div class="mb-3">
      <label class="form-label">Choose a plan</label>
      <select name="price_id" class="form-select" required>
        @foreach($prices as $p)
          <option value="{{ $p->stripe_price_id }}">
            {{ $p->name }} — ${{ number_format($p->amount/100, 2) }}/mo
          </option>
        @endforeach
      </select>
    </div>

    <button class="btn btn-primary">Continue to Checkout</button>
  </form>
</div>
@endsection