<!doctype html>
<html lang="en" data-bs-theme="auto">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <!-- Dashboard CSS -->
        <link href="#" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

        <!-- Custom Styles -->
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

        @yield('styles')
        @livewireStyles 
    </head>
    <body class="bg-light">
        <div class="container-fluid">
            <!-- content here -->
            @yield('content')            
        </div>
        @include('sweetalert2::index')

        @if (session('swal'))
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire(@json(session('swal')));
        });
        </script>
        @endif

        <!-- Jquery JS -->
        <script
            src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>
        <script src="//cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @stack('scripts')   
        @livewireScripts  
    </body>
</html>