@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">My Subscription Plan 1</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			
			
			
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">My Subscription Plan</h1>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded border bg-white p-5 shadow-sm">
        <form method="POST" action="{{ route('creator.subscription.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block font-medium mb-1" for="title">Title</label>
                <input id="title" name="title" type="text" class="w-full border rounded p-2"
                       value="{{ old('title', $subscription?->title ?? 'Creator Plan') }}" required>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1" for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full border rounded p-2"
                          placeholder="Describe what subscribers get...">{{ old('description', $subscription?->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1" for="price">Price (USD)</label>
                    <input id="price" name="price" type="number" step="0.01" min="1" class="w-full border rounded p-2"
                           value="{{ old('price', $subscription?->price ?? 9.99) }}" required>
                </div>
                <div>
                    <label class="block font-medium mb-1" for="interval">Interval</label>
                    <select id="interval" name="interval" class="w-full border rounded p-2" required>
                        @php $interval = old('interval', $subscription?->interval ?? 'month'); @endphp
                        <option value="month" @selected($interval === 'month')>Monthly</option>
                        <option value="year"  @selected($interval === 'year')>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">
                    Save Plan
                </button>

                @if($subscription)
                    <span class="text-sm text-gray-600">
                        Current: ${{ number_format($subscription->price, 2) }}/{{ $subscription->interval }}
                    </span>
                @endif
            </div>
        </form>
    </div>
</div>



            </div>
        </div>
      </div>
    </div>
@endsection