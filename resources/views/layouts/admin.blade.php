<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Admin Panel') — MyFans</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Your CSS --}}
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        

    {{-- SweetAlert2 (if you use it globally) --}}
    @includeIf('sweetalert2::index')

    <style>
        :root {
            --admin-sidebar-width: 260px;
        }
        body {
            min-height: 100vh;
            display: flex;
            background: #f8f9fa;
        }
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background: #1f1f2e; /* deep purple/indigo vibe */
            color: #fff;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .admin-brand {
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: .5px;
        }
        .admin-content {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            padding: 24px;
        }
        .nav-link {
            color: rgba(255,255,255,.85);
        }
        .nav-link.active, .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.08);
            border-radius: .5rem;
        }
        .sidebar-section {
            text-transform: uppercase;
            font-size: .75rem;
            opacity: .7;
            margin-top: 1rem;
            margin-bottom: .5rem;
        }
    </style>

    @stack('head')
</head>
<body>

    {{-- Sidebar --}}
    @include('layouts.partials.admin.sidebar')

    {{-- Main --}}
    <main class="admin-content">
        {{-- Top bar (optional) --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">@yield('page_title', 'Dashboard')</h1>
                @yield('breadcrumbs')
            </div>
            <div class="d-flex align-items-center gap-2">
                @auth
                    <span class="text-muted small me-2">{{ auth()->user()->name }}</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard') }}">User Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger">Logout</button>
                    </form>
                @endauth
            </div>
        </div>

        {{-- Flash SweetAlert --}}
        @if (session('swal'))
        <script>
        document.addEventListener('DOMContentLoaded', () => { Swal.fire(@json(session('swal'))); });
        </script>
        @endif

        {{-- Content --}}
        @yield('content')
    </main>

    {{-- JS --}}
    <script src="{{ asset('js/app.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>
</html>
