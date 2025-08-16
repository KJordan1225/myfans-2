@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">My Subscriptions 1</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			
			
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">My Subscriptions</h1>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($subscriptions->isEmpty())
        <div class="rounded border bg-white p-5 shadow-sm">
            <p class="text-gray-700">You don’t have any active subscriptions yet.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($subscriptions as $subscription)
                @php
                    $creator = $subscription->creator;
                    $profile = $creator?->creatorProfile;
                    $pivot   = $subscription->pivot ?? null; // starts_at, ends_at, status, is_active, price_snapshot
                    $active  = $pivot && $pivot->is_active && $pivot->status === 'active' && (empty($pivot->ends_at) || \Illuminate\Support\Carbon::parse($pivot->ends_at)->isFuture());
                @endphp

                <div class="rounded border bg-white shadow-sm overflow-hidden">
                    <div class="p-4 border-b bg-gray-50">
                        <div class="flex items-center gap-3">
                            {{-- Avatar (if using paths or media library) --}}
                            <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden">
                                @if($profile?->avatar_path)
                                    <img src="{{ asset('storage/' . $profile->avatar_path) }}" alt="Avatar" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold">
                                    {{ $profile?->display_name ?? $creator?->name ?? 'Creator' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Plan: ${{ number_format($subscription->price, 2) }}/{{ $subscription->interval }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 text-sm text-gray-700 min-h-16">
                        {{ \Illuminate\Support\Str::limit($subscription->description, 140) }}
                    </div>

                    <div class="px-4 pb-4 text-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-gray-600">
                                    Since:
                                    <span class="font-medium">
                                        {{ $pivot?->starts_at ? \Illuminate\Support\Carbon::parse($pivot->starts_at)->toFormattedDateString() : '—' }}
                                    </span>
                                </div>
                                <div class="text-gray-600">
                                    Status:
                                    <span class="inline-block px-2 py-0.5 rounded text-xs 
                                        {{ $active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $pivot?->status ?? 'unknown' }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right">
                                @if($active)
                                    <form method="POST" action="{{ route('subscriptions.cancel', $subscription) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            Cancel
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('subscriptions.resume', $subscription) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            Resume
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Optional: show the price actually paid at purchase time --}}
                        @if(!empty($pivot?->price_snapshot))
                            <div class="text-xs text-gray-500">
                                You paid: ${{ number_format($pivot->price_snapshot, 2) }} (snapshot)
                            </div>
                        @endif

                        {{-- Optional: provider id for support --}}
                        @if(!empty($pivot?->provider_subscription_id))
                            <div class="text-xs text-gray-400">
                                Ref: #{{ \Illuminate\Support\Str::limit($pivot->provider_subscription_id, 14) }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>


            </div>
        </div>
      </div>
    </div>
@endsection