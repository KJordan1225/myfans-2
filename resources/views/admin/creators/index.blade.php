@extends('layouts.admin')

@section('title', 'Creators')
@section('page_title', 'Creators')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="name, email, username">
            </div>
            <div class="col-md-4">
                <label class="form-label">Connect Status</label>
                <select name="connect" class="form-select">
                    <option value="">Any</option>
                    <option value="onboarded" @selected($status==='onboarded')>Onboarded</option>
                    <option value="not-onboarded" @selected($status==='not-onboarded')>Not Onboarded</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Per page</label>
                <select name="perPage" class="form-select">
                    @foreach([12,24,48,96] as $pp)
                        <option value="{{ $pp }}" @selected($perPage==$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mt-2">
@forelse($creators as $creator)
    @php
        $banner = $creator->profile?->getFirstMediaUrl('banner', 'cover')
                  ?: $creator->profile?->getFirstMediaUrl('banner')
                  ?: asset('images/placeholders/banner-1200x250.png');

        $avatar = $creator->profile?->getFirstMediaUrl('avatar', 'thumb')
                  ?: $creator->profile?->getFirstMediaUrl('avatar')
                  ?: asset('images/placeholders/avatar-100.png');

        $acctId  = $creator->profile?->stripe_account_id;
        $obAt    = $creator->profile?->stripe_onboarded_at;
        $isReady = !empty($acctId);
    @endphp

    <div class="col-lg-6">
        <div class="card shadow-sm overflow-hidden">
            <div class="position-relative" style="height: 160px; background:#f1f1f1;">
                <img src="{{ $banner }}" class="w-100 h-100" style="object-fit: cover;" alt="Banner">
                <img src="{{ $avatar }}" class="rounded-circle border border-3 border-white position-absolute"
                     alt="Avatar"
                     style="width:84px;height:84px;object-fit:cover;left:16px;bottom:-42px;">
            </div>

            <div class="card-body" style="padding-top: 54px;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="h5 mb-0">{{ $creator->name }}</div>
                        <div class="text-muted small">{{ '@'.$creator->username }}</div>
                        <div class="text-muted small">{{ $creator->email }}</div>
                    </div>
                    <div class="text-end">
                        @if($isReady)
                            <span class="badge bg-success">Onboarded</span>
                        @else
                            <span class="badge bg-warning text-dark">Needs Onboarding</span>
                        @endif
                    </div>
                </div>

                <div class="small text-muted">
                    <div>Account ID: <code>{{ $acctId ?: '—' }}</code></div>
                    <div>Onboarded at: {{ $obAt?->toDayDateTimeString() ?? '—' }}</div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    {{-- View as creator (link to your public creator page) --}}
                    <a href="#" class="btn btn-sm btn-outline-primary" target="_blank">
                        View as creator
                    </a>

                    {{-- Resend onboarding --}}
                    <form action="{{ route('admin.creators.resend-onboarding', $creator) }}" method="POST"
                          onsubmit="return confirm('Generate a new onboarding link for {{ $creator->name }}?')">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">
                            Resend onboarding link
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="alert alert-info">No creators found.</div>
    </div>
@endforelse
</div>

@if($creators->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $creators->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
