{{-- Unified sidebar matching menu2 look & feel --}}
<aside class="admin-sidebar d-flex flex-column p-3">
    {{-- Brand/Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="admin-brand">
            <a href="{{ route('dashboard') }}" class="text-decoration-none text-white">
                MyFans
            </a>
        </div>
    </div>

    {{-- Main --}}
    <div class="sidebar-section">Main</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
    </ul>

    {{-- Profile --}}
    <div class="sidebar-section">Profile</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('user-profile.*') ? 'active' : '' }}"
               href="{{ route('user-profile.index') }}">
                <i class="bi bi-person-gear me-2"></i> Create / Edit Profile
            </a>
        </li>
    </ul>

    {{-- Creator (role-gated) --}}
    @role('creator')
    <div class="sidebar-section">Creator</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('creator.monetize') ? 'active' : '' }}"
               href="{{ route('creator.monetize') }}">
                <i class="bi bi-currency-exchange me-2"></i> Onboard with Stripe
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('creator.plans.index') ? 'active' : '' }}"
               href="{{ route('creator.plans.index') }}">
                <i class="bi bi-tags me-2"></i> View Subscription Plans
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('creator.plans.create') ? 'active' : '' }}"
               href="{{ route('creator.plans.create') }}">
                <i class="bi bi-tag-plus me-2"></i> Create Subscription Plans
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('creator.posts.list') ? 'active' : '' }}"
               href="{{ route('creator.posts.list') }}">
                <i class="bi bi-images me-2"></i> Posts
            </a>
        </li>
    </ul>
    @endrole

    {{-- Subscriptions (viewer/fan side) --}}
    <div class="sidebar-section">Subscriptions</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('subscriptions.mine') ? 'active' : '' }}"
               href="{{ route('subscriptions.mine') }}">
                <i class="bi bi-repeat me-2"></i> My Subscriptions
            </a>
        </li>
    </ul>

    {{-- Admin entry (role-gated) --}}
    @role('admin|super-admin')
    <div class="sidebar-section">System</div>
    <ul class="nav nav-pills flex-column mt-auto">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <i class="bi bi-shield-lock me-2"></i> Admin Panel
            </a>
        </li>
    </ul>
    @endrole

    {{-- Footer / Logged-in hint --}}
    <div class="mt-4 small text-white-50">
        <strong>Logged in as:</strong> {{ auth()->user()->name }}
    </div>
</aside>
