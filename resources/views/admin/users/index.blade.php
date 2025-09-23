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

        <form id="bulk-form" method="POST" action="{{ route('admin.users.bulk') }}" class="card shadow-sm mt-3">
            @csrf
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select name="action" id="bulk-action" class="form-select" required>
                        <option value="">Choose…</option>
                        <option value="assign_role">Assign role</option>
                        <option value="remove_role">Remove role</option>
                        <option value="suspend">Suspend</option>
                        <option value="unsuspend">Unsuspend</option>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="bulk-role-wrap">
                    <label class="form-label">Role</label>
                    <select name="role" id="bulk-role" class="form-select">
                        <option value="">Choose role…</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" id="bulk-apply" class="btn btn-primary" disabled>Apply to selected</button>
                </div>
                <div class="col-md-3 text-end text-muted small">
                    <span id="bulk-selected-count">0 selected</span>
                </div>
            </div>

            {{-- We’ll append hidden inputs for selected IDs here via JS --}}
            <div id="bulk-hidden-inputs"></div>
        </form>



    </div>
</div>

<div class="table-responsive mt-3">
    <table class="table align-middle" id="users-table">
        <thead>
            <tr>
                <th style="width:36px">
                    <input type="checkbox" id="select-all">
                </th>
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
                    $avatar = $user->profile?->getFirstMediaUrl('avatar', 'thumb')
                            ?: $user->profile?->getFirstMediaUrl('avatar')
                            ?: asset('images/placeholders/avatar-100.png');
                @endphp
                <tr>
                    <td>
                        <input type="checkbox" class="row-check" value="{{ $user->id }}">
                    </td>
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
                        {{-- existing per-user buttons (suspend / make admin / etc.) --}}
                        {{-- ... --}}
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No users found.</td></tr>
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

@push('scripts')
<script>
(function () {
    const selectAll   = document.getElementById('select-all');
    const checks      = () => Array.from(document.querySelectorAll('.row-check'));
    const bulkForm    = document.getElementById('bulk-form');
    const hiddenWrap  = document.getElementById('bulk-hidden-inputs');
    const applyBtn    = document.getElementById('bulk-apply');
    const selectedTxt = document.getElementById('bulk-selected-count');

    const bulkAction  = document.getElementById('bulk-action');
    const roleWrap    = document.getElementById('bulk-role-wrap');

    function syncSelected() {
        const selected = checks().filter(c => c.checked).map(c => c.value);
        hiddenWrap.innerHTML = '';
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            hiddenWrap.appendChild(input);
        });
        applyBtn.disabled = selected.length === 0;
        selectedTxt.textContent = `${selected.length} selected`;
    }

    if (selectAll) {
        selectAll.addEventListener('change', (e) => {
            checks().forEach(c => c.checked = e.target.checked);
            syncSelected();
        });
    }

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('row-check')) {
            // If any row unchecked, uncheck master
            if (!e.target.checked && selectAll) selectAll.checked = false;
            syncSelected();
        }
    });

    // Toggle role select visibility when action changes
    bulkAction.addEventListener('change', () => {
        if (['assign_role','remove_role'].includes(bulkAction.value)) {
            roleWrap.classList.remove('d-none');
        } else {
            roleWrap.classList.add('d-none');
        }
    });

    // Optional SweetAlert confirm
    bulkForm.addEventListener('submit', (e) => {
        if (typeof Swal === 'undefined') return; // no swal, just submit
        e.preventDefault();

        const action = bulkAction.value || '(choose action)';
        Swal.fire({
            icon: 'question',
            title: 'Apply bulk action?',
            text: `You are about to "${action.replace('_',' ')}" on selected users.`,
            showCancelButton: true,
            confirmButtonText: 'Yes, do it',
        }).then((result) => {
            if (result.isConfirmed) bulkForm.submit();
        });
    });

    // Initialize state
    syncSelected();
})();
</script>
@endpush

