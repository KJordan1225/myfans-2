<aside class="admin-sidebar d-flex flex-column p-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="admin-brand">
            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-white">
                MyFans Admin
            </a>
        </div>
    </div>

    <div class="sidebar-section">Main</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
    </ul>

    <div class="sidebar-section">Manage</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
               href="{{ route('admin.users.index') }}">
                <i class="bi bi-people me-2"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.creators.*') ? 'active' : '' }}"
               href="{{ route('admin.creators.index') }}">
                <i class="bi bi-person-badge me-2"></i> Creators
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
               href="{{ route('admin.plans.index') }}">
                <i class="bi bi-tags me-2"></i> Plans
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}"
               href="{{ route('admin.subscriptions.index') }}">
                <i class="bi bi-repeat me-2"></i> Subscriptions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}"
               href="{{ route('admin.posts.index') }}">
                <i class="bi bi-images me-2"></i> Posts
            </a>
        </li>
    </ul>

    <div class="sidebar-section">Insights</div>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
               href="{{ route('admin.reports.index') }}">
                <i class="bi bi-bar-chart-line me-2"></i> Reports
            </a>
        </li>
    </ul>

    <div class="sidebar-section">System</div>
    <ul class="nav nav-pills flex-column mt-auto">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
               href="{{ route('admin.settings.index') }}">
                <i class="bi bi-gear me-2"></i> Settings
            </a>
        </li>
    </ul>
</aside>
