@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Purchase Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
  <div class="row">
    <div class="col-md-6 offset-md-3">

      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Support {{ $creator->name }}</h5>
        </div>
        <div class="card-body">

          {{-- Success (from server) -> Toast --}}
          @if(session('success'))
            <script>
              document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                  toast: true, position: 'top-end', timer: 2500, showConfirmButton: false,
                  icon: 'success', title: @json(session('success'))
                });
              });
            </script>
          @endif

          {{-- Error (from server) -> Toast --}}
          @if($errors->any())
            <script>
              document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                  toast: true, position: 'top-end', timer: 3500, showConfirmButton: false,
                  icon: 'error', title: 'Please fix the errors and try again.'
                });
              });
            </script>
          @endif

          <form id="payment-form" class="needs-validation" novalidate>
            @csrf
            <input type="hidden" id="creator_id" value="{{ $creator->id }}">

            <div class="mb-3">
              <label for="amount" class="form-label">Amount (USD)</label>
              <input type="number" step="0.01" min="0.50" max="9999" id="amount" class="form-control"
                     value="{{ number_format($defaultPrice, 2, '.', '') }}" required>
              <div class="form-text">
                Platform fee: {{ $platformFeePercent }}% (min ${{ number_format($platformFeeMin, 2) }})
              </div>
              <div class="invalid-feedback">Please enter a valid amount (min $0.50).</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Card details</label>
              <div id="card-element" class="form-control py-2"></div>
              <div id="card-errors" class="invalid-feedback d-block mt-1" role="alert"></div>
            </div>

            <button id="pay-button" type="submit" class="btn btn-primary w-100">
              Pay {{ number_format($defaultPrice, 2) }} to {{ $creator->display_name }}
            </button>
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

{{-- Stripe.js --}}
<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
  const stripe = Stripe(@json($stripePublishableKey));
  const elements = stripe.elements();
  const card = elements.create('card');
  card.mount('#card-element');

  const form = document.getElementById('payment-form');
  const payBtn = document.getElementById('pay-button');
  const amountInput = document.getElementById('amount');
  const creatorId = document.getElementById('creator_id').value;
  const cardErrors = document.getElementById('card-errors');

  // Helper: show toast
  function toast(icon, title) {
    Swal.fire({ toast: true, position: 'top-end', timer: 2800, showConfirmButton: false, icon, title });
  }

  // Reflect amount on the button label
  amountInput.addEventListener('input', () => {
    const v = parseFloat(amountInput.value || '0').toFixed(2);
    payBtn.textContent = `Pay ${v} to {{ $creator->display_name }}`;
  });

  // Basic front-end validation
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    cardErrors.textContent = '';

    const rawAmount = parseFloat(amountInput.value);
    if (isNaN(rawAmount) || rawAmount < 0.5) {
      toast('error', 'Please enter at least $0.50');
      return;
    }

    payBtn.disabled = true;
    payBtn.textContent = 'Processing…';

    try {
      // Get clientSecret from your server
      const resp = await fetch(@json(route('purchase.intent')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          creator_id: creatorId,
          amount: rawAmount,
        }),
      });

      if (!resp.ok) {
        const err = await resp.json().catch(() => ({}));
        throw new Error(err.message || 'Failed to create payment.');
      }

      const { clientSecret } = await resp.json();

      // Confirm the payment on the client with the card Element
      const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
          card,
          billing_details: {
            // You can pass buyer info here if you collect it
            name: @json(auth()->user()->name),
            email: @json(auth()->user()->email),
          },
        },
      });

      if (error) {
        cardErrors.textContent = error.message || 'Payment failed.';
        toast('error', error.message || 'Payment failed.');
        payBtn.disabled = false;
        payBtn.textContent = `Pay ${rawAmount.toFixed(2)} to {{ $creator->name }}`;
        return;
      }

      if (paymentIntent && paymentIntent.status === 'succeeded') {
        toast('success', 'Payment successful!');
        // TODO: Optionally redirect to a “thank you” page or refresh
        setTimeout(() => window.location.reload(), 800);
      } else {
        toast('warning', 'Payment processing. Please check your email/statement.');
        setTimeout(() => window.location.reload(), 1200);
      }

    } catch (err) {
      console.error(err);
      toast('error', err.message || 'Unexpected error.');
      payBtn.disabled = false;
      payBtn.textContent = `Pay ${parseFloat(amountInput.value || '0').toFixed(2)} to {{ $creator->name }}`;
    }
  });
})();
</script>

@endpush
