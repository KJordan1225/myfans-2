@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Admin Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Users</div>
                <div class="display-6 fw-bold">{{ number_format($stats['users'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Creators (Onboarded)</div>
                <div class="display-6 fw-bold">{{ number_format($stats['creators'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Active Plans</div>
                <div class="display-6 fw-bold">{{ number_format($stats['active_plans'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Subscriptions</div>
                <div class="display-6 fw-bold">{{ number_format($stats['subscriptions'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Posts</div>
                <div class="display-6 fw-bold">{{ number_format($stats['posts'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">MRR</div>
                <div class="display-6 fw-bold">${{ number_format($stats['mrr'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
