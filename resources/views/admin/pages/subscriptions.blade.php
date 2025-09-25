{{-- resources/views/admin/pages/subscriptions.blade.php --}}
@extends('layouts.admin')

@section('title', 'Subscriptions')
@section('page_title', 'Subscriptions')

@section('content')
@php
    // Map statuses to Bootstrap badges (tweak as needed)
    $statusClass = [
        'active'              => 'success',
        'trialing'            => 'info',
        'incomplete'          => 'warning',
        'incomplete_expired'  => 'secondary',
        'past_due'            => 'warning',
        'paused'              => 'secondary',
        'canceled'            => 'danger',
        'unpaid'              => 'danger',
        'unknown'             => 'secondary',
        null                  => 'secondary',
    ];
@endphp

{{-- Filters --}}
<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="user email/name, creator name, plan name, sub id">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @php $cur = request('status'); @endphp
                    <option value="">Any</option>
                    @foreach(['active','trialing','incomplete','incomplete_expired','past_due','paused','canceled','unpaid'] as $st)
                        <option value="{{ $st }}" @selected($cur===$st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Per page</label>
                <select name="perPage" class="form-select">
                    @php $pp = (int) request('perPage', 15); @endphp
                    @foreach([10,15,25,50,100] as $n)
                        <option value="{{ $n }}" @selected($pp===$n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="table-responsive mt-3">
    <table class="table align-middle">
        <thead>
        <tr>
            <th>ID</th>
            <th>Subscriber</th>
            <th>Creator</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Started</th>
            <th>Renews</th>
            <th class="text-end">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($subs as $sub)
            @php
                // Adjust these accessors to match your model fields
                $subscriber = $sub->user ?? null;
                $creator    = $sub->creator ?? null;
                $plan       = $sub->plan ?? null;

                $planLabel  = $plan?->name
                             ?? ($sub->plan_name ?? '—');

                // If you store amount in cents:
                $amount     = $plan?->amount ?? $sub->amount ?? null; // cents
                $currency   = strtoupper($plan?->currency ?? $sub->currency ?? 'USD');
                $interval   = $plan?->interval ?? $sub->interval ?? 'month';
                $priceText  = $amount !== null ? ('$'.number_format($amount/100, 2).' / '.$interval) : '—';

                $status     = $sub->status ?? 'unknown';
                $badge      = $statusClass[$status] ?? 'secondary';

                $startedAt  = optional($sub->created_at)->toDayDateTimeString() ?? '—';
                $periodEnd  = optional($sub->current_period_end)->toDayDateTimeString() ?? '—';

                // Optional Stripe IDs if you want external links
                $stripeSubId = $sub->stripe_subscription_id ?? null;
            @endphp

            <tr>
                <td>#{{ $sub->id }}</td>

                <td>
                    @if($subscriber)
                        <div class="fw-semibold">{{ $subscriber->name }}</div>
                        <div class="small text-muted">{{ $subscriber->email }}</div>
                        <div class="small text-muted">{{ '@'.($subscriber->username ?? Str::slug($subscriber->name)) }}</div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td>
                    @if($creator)
                        <div class="fw-semibold">{{ $creator->name }}</div>
                        <div class="small text-muted">{{ '@'.($creator->username ?? Str::slug($creator->name)) }}</div>
                        <a href="#" class="small">View public</a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td>
                    <div>{{ $planLabel }}</div>
                    <div class="small text-muted">{{ $priceText }} <span class="text-uppercase">{{ $currency }}</span></div>
                </td>

                <td>
                    <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_',' ',$status)) }}</span>
                </td>

                <td>{{ $startedAt }}</td>
                <td>{{ $periodEnd }}</td>

                <td class="text-end">
                    {{-- Example actions (wire routes if you have them) --}}
                    @if($stripeSubId)
                        <a href="https://dashboard.stripe.com/subscriptions/{{ $stripeSubId }}"
                           target="_blank"
                           class="btn btn-sm btn-outline-secondary">
                            Stripe
                        </a>
                    @endif>

                    {{-- If you add an admin cancel route, enable this:
                    <form class="d-inline" method="POST" action="{{ route('admin.subscriptions.cancel', $sub->id) }}"
                          onsubmit="return confirm('Cancel this subscription?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                    --}}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-5">No subscriptions found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($subs->hasPages())
    <div class="d-flex justify-content-center">
        {{ $subs->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
