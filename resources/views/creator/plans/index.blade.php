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
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <h2 class="mb-3">Your Membership Plans</h2>

            {{-- Flash toasts --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- Create Plan --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Create a Plan</h5>
                    <form action="{{ route('creator.plans.store') }}" method="POST" class="row g-3">
                        @csrf

                        <div class="col-md-6">
                            <label class="form-label">Plan name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Monthly $9.99" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="1" name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', '9.99') }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" maxlength="3"
                                   class="form-control text-uppercase @error('currency') is-invalid @enderror"
                                   value="{{ old('currency', 'USD') }}" required>
                            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Interval unit</label>
                            <select name="interval_unit" class="form-select @error('interval_unit') is-invalid @enderror" required>
                                @php $units = ['DAY','WEEK','MONTH','YEAR']; @endphp
                                @foreach($units as $u)
                                    <option value="{{ $u }}" @selected(old('interval_unit','MONTH') === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                            @error('interval_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Interval count</label>
                            <input type="number" min="1" max="12" name="interval_count"
                                   class="form-control @error('interval_count') is-invalid @enderror"
                                   value="{{ old('interval_count', 1) }}" required>
                            @error('interval_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                                       @checked(old('active', true))>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary">Create Plan on PayPal</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Existing Plans --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Existing Plans</h5>

                    @if($plans->isEmpty())
                        <div class="text-muted">No plans yet. Create one above.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Interval</th>
                                    <th>Status</th>
                                    <th>PayPal Product</th>
                                    <th>PayPal Plan</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($plans as $p)
                                    <tr>
                                        <td>{{ $p->name }}</td>
                                        <td>{{ $p->currency }} {{ number_format($p->amount, 2) }}</td>
                                        <td>
                                            @if($p->interval_count == 1)
                                                {{ $p->interval_unit }}
                                            @else
                                                every {{ $p->interval_count }} {{ strtolower($p->interval_unit) }}s
                                            @endif
                                        </td>
                                        <td>
                                            @if($p->active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-truncate" style="max-width: 160px;">
                                            <code>{{ $p->paypal_product_id ?? '—' }}</code>
                                        </td>
                                        <td class="text-truncate" style="max-width: 200px;">
                                            <code>{{ $p->paypal_plan_id ?? '—' }}</code>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
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