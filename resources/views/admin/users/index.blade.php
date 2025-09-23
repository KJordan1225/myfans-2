@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="name, email, username">
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="">Any</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected($role===$r)>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Any</option>
                    <option value="active" @selected($status==='active')>Active</option>
                    <option value="suspended" @selected($status==='suspended')>Suspended</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Per page</label>
                <select name="perPage" class="form-select">
                    @foreach([10,15,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected($perPage==$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive mt-3">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>User</th>
                <th>Roles</th>
                <th>Email</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                @php
                    $avatar = $user->profile?->getFirstMediaUrl('avatar', 'thumb') ?: $user->profile?->getFirstMediaUrl('avatar') ?: asset('images/placeholders/avatar-100.png');
                @endphp
                <tr>
                    <td>
                        <img src="{{ $avatar }}" alt="{{ $user->name }} avatar" class="rounded-circle" width="44" height="44">
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ '@'.$user->username }}</div>
                    </td>
                    <td>
                        <div class="small">
                            {{ $user->roles->pluck('name')->map(fn($r)=>ucfirst($r))->implode(', ') ?: '—' }}
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->is_suspended ?? false)
                            <span class="badge bg-danger">Suspended</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                    </td>
                    <td class="text-end">
                        {{-- Toggle suspend --}}
                        <form action="{{ route('admin.users.toggle-suspend', $user) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning"
                                onclick="return confirm('Toggle suspension for {{ $user->name }}?')">
                                {{ ($user->is_suspended ?? false) ? 'Unsuspend' : 'Suspend' }}
                            </button>
                        </form>

                        {{-- Grant/Revoke admin --}}
                        @if(!$user->hasRole('admin'))
                            <form action="{{ route('admin.users.grant-admin', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="return confirm('Grant admin to {{ $user->name }}?')">
                                    Make Admin
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.users.revoke-admin', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary"
                                    onclick="return confirm('Revoke admin from {{ $user->name }}?')">
                                    Remove Admin
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div class="d-flex justify-content-center">
        {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
