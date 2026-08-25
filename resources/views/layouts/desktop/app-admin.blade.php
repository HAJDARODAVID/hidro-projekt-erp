<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ url('/images/hpIcon.png') }}" type="image/x-icon"/>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <!-- Scripts -->
    @livewireStyles
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('assets/css/dashboard.css') }}" rel="stylesheet">
    <link href="{{ url('assets/css/application.css') }}" rel="stylesheet">
    <style>
        main.col-md-9,
        main.col-lg-10 {
            background: #f4f6fb;
            min-height: calc(100vh - 48px);
        }

        /* ---------- Widget grid ---------- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        .dashboard-grid-item--quick-actions,
        .dashboard-grid-item--quick-stats {
            grid-column: 1 / -1;
        }

        .dashboard-grid .card {
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 4px 10px rgba(16, 24, 40, .05) !important;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="h-100">

    @livewire('exception-modal')
    @livewire('alert-modal')
    <x-ui.layouts.header />
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="d-flex flex-column position-sticky pt-3 sidebar-sticky">
                    @livewire('main-menu')
                    @livewire('my-profile-component')
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                @yield('content')
            </main>
        </div>
    </div>
    <x-ui.notification />
    @include('components.ui.quick-access-modal')
    @include('components.ui.calculator-modal')
    @livewire('components.modal.global-modal')
    @livewireScripts

    <script>
        document.addEventListener('livewire:load', () => {
            Livewire.hook('message.sent', () => {
                document.body.classList.add('livewire-loading');
            });
            Livewire.hook('message.processed', () => {
                document.body.classList.remove('livewire-loading');
            });
        });
    </script>
    <script>

        const clockElement = document.getElementById('clock-display');

        /**
         * Updates the clock and date display.
         */
        function updateClock() {
            const now = new Date();

            // Format the time (HH:MM:SS AM/PM)
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            // Update the DOM elements
            clockElement.textContent = timeString;
        }

        // 1. Initial call to display the clock immediately
        updateClock();

        // 2. Set up an interval to call updateClock() every 1000 milliseconds (1 second)
        setInterval(updateClock, 1000);

    </script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous">
    </script>
    <script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
