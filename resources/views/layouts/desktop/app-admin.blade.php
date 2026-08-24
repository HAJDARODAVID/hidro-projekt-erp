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
    <link href="{{ url('assets/css/application.css') }}" rel="stylesheet">
    <style>
        :root {
            --dsk-sidebar-w: 264px;
            --dsk-sidebar-w-collapsed: 84px;
            --dsk-topbar-h: 68px;
            --dsk-accent: #4f7cff;
            --dsk-accent-soft: rgba(79, 124, 255, .15);
            --dsk-sidebar-bg: #12182b;
            --dsk-sidebar-bg-alt: #171f38;
            --dsk-sidebar-text: #aab3c9;
            --dsk-sidebar-text-strong: #f5f7fb;
            --dsk-content-bg: #f4f6fb;
        }

        html, body {
            height: 100%;
        }

        body.desktop-admin {
            background: var(--dsk-content-bg);
            overflow-x: hidden;
        }

        .desktop-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        .desktop-sidebar {
            width: var(--dsk-sidebar-w);
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--dsk-sidebar-bg) 0%, var(--dsk-sidebar-bg-alt) 100%);
            color: var(--dsk-sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            transition: transform .25s ease, width .25s ease;
        }

        .desktop-shell.collapsed .desktop-sidebar {
            width: var(--dsk-sidebar-w-collapsed);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1.15rem 1.25rem;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-brand img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            color: var(--dsk-sidebar-text-strong);
            font-weight: 700;
            font-size: 1.02rem;
            letter-spacing: .2px;
            opacity: 1;
            transition: opacity .15s ease;
        }

        .desktop-shell.collapsed .sidebar-brand-text {
            opacity: 0;
            width: 0;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-top: .25rem;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .12);
            border-radius: 3px;
        }

        /* Restyle the shared main-menu / my-profile-component list-groups to fit the dark sidebar */
        .desktop-sidebar .list-group-item {
            background: transparent;
            border: 0;
            color: var(--dsk-sidebar-text);
            border-radius: 10px;
            margin: 2px 10px;
            transition: background .15s ease, color .15s ease;
        }

        .desktop-sidebar .list-group-item b {
            color: inherit !important;
        }

        .desktop-sidebar .list-group-item:hover {
            background: rgba(255, 255, 255, .06);
            color: var(--dsk-sidebar-text-strong);
        }

        .desktop-sidebar .list-group-item.active {
            background: var(--dsk-accent-soft);
            color: var(--dsk-sidebar-text-strong);
        }

        .desktop-sidebar .list-group-item.active i {
            color: var(--dsk-accent);
        }

        .desktop-sidebar hr {
            border-color: rgba(255, 255, 255, .08);
            margin: .5rem 1rem;
        }

        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .sidebar-footer strong {
            color: var(--dsk-sidebar-text-strong);
        }

        .desktop-shell.collapsed .sidebar-menu,
        .desktop-shell.collapsed .sidebar-footer {
            opacity: 0;
            pointer-events: none;
        }

        /* ---------- Sidebar backdrop (narrow viewports) ---------- */
        .desktop-sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(10, 14, 25, .45);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
            z-index: 1029;
        }

        /* ---------- Main column ---------- */
        .desktop-main {
            flex: 1;
            min-width: 0;
            margin-left: var(--dsk-sidebar-w);
            display: flex;
            flex-direction: column;
            transition: margin-left .25s ease;
        }

        .desktop-shell.collapsed .desktop-main {
            margin-left: var(--dsk-sidebar-w-collapsed);
        }

        .desktop-topbar {
            height: var(--dsk-topbar-h);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 0 1.5rem;
            background: #fff;
            border-bottom: 1px solid #eaedf3;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .desktop-topbar .btn-icon {
            background: transparent;
            border: 0;
            color: #4b5568;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .desktop-topbar .btn-icon:hover {
            background: #f1f3f9;
            color: #1b1e21;
        }

        .topbar-greeting {
            flex: 1;
            min-width: 0;
        }

        .topbar-greeting h5 {
            font-weight: 700;
            font-size: 1.05rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .desktop-content {
            flex: 1;
            padding: 1.75rem;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        /* Give the reused Bootstrap cards a slightly softer, more modern feel */
        .desktop-content .card {
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 4px 10px rgba(16, 24, 40, .05) !important;
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

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ---------- Responsive sidebar (narrow viewports) ---------- */
        @media (max-width: 991.98px) {
            .desktop-sidebar {
                transform: translateX(-100%);
                width: var(--dsk-sidebar-w) !important;
            }

            .desktop-shell.sidebar-open .desktop-sidebar {
                transform: translateX(0);
            }

            .desktop-shell.sidebar-open .desktop-sidebar-backdrop {
                opacity: 1;
                pointer-events: auto;
            }

            .desktop-main,
            .desktop-shell.collapsed .desktop-main {
                margin-left: 0;
            }

            .desktop-content {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body class="desktop-admin h-100">

    @livewire('exception-modal')
    @livewire('alert-modal')

    <div class="desktop-shell" id="desktopShell">

        <div class="desktop-sidebar-backdrop" id="desktopSidebarBackdrop"></div>

        <aside class="desktop-sidebar">
            <div class="sidebar-brand">
                <img src="{{ asset('images/hpIcon.png') }}" alt="">
                <span class="sidebar-brand-text">{{ config('app.name') }}</span>
            </div>
            <div class="sidebar-menu">
                @livewire('main-menu')
            </div>
            <div class="sidebar-footer">
                @livewire('my-profile-component')
            </div>
        </aside>

        <div class="desktop-main">
            <header class="desktop-topbar">
                <button type="button" class="btn-icon" id="desktopSidebarToggle" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="topbar-greeting">
                    <div class="text-muted small">{{ \Carbon\Carbon::now()->translatedFormat('l, d.m.Y.') }}</div>
                    <h5 class="mb-0">{{ __('Dobrodošli') }}, {{ Auth::user()->name }}</h5>
                </div>

                <div class="topbar-actions">
                    @livewire('warning-indicator')
                    <button type="button" class="btn-icon" id="desktopQuickAccessBtn" title="{{ __('Brzi pristup') }} (Ctrl+Q)">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('logout') }}" class="btn-icon" title="{{ __('Odjava') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </header>

            <main class="desktop-content">
                @yield('content')
            </main>
        </div>
    </div>

    <x-ui.notification />
    @include('components.ui.quick-access-modal')
    @include('components.ui.calculator-modal')
    @livewire('components.modal.global-modal')
    @livewireScripts

    <script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        (function () {
            const shell = document.getElementById('desktopShell');
            const toggleBtn = document.getElementById('desktopSidebarToggle');
            const backdrop = document.getElementById('desktopSidebarBackdrop');
            const quickAccessBtn = document.getElementById('desktopQuickAccessBtn');
            const isNarrow = () => window.matchMedia('(max-width: 991.98px)').matches;
            const STORAGE_KEY = 'dsk_sidebar_collapsed';

            if (!isNarrow() && localStorage.getItem(STORAGE_KEY) === '1') {
                shell.classList.add('collapsed');
            }

            toggleBtn.addEventListener('click', function () {
                if (isNarrow()) {
                    shell.classList.toggle('sidebar-open');
                } else {
                    shell.classList.toggle('collapsed');
                    localStorage.setItem(STORAGE_KEY, shell.classList.contains('collapsed') ? '1' : '0');
                }
            });

            backdrop.addEventListener('click', function () {
                shell.classList.remove('sidebar-open');
            });

            shell.addEventListener('click', function (e) {
                if (isNarrow() && shell.classList.contains('sidebar-open') && e.target.closest('.desktop-sidebar a[href]')) {
                    shell.classList.remove('sidebar-open');
                }
            });

            if (quickAccessBtn) {
                quickAccessBtn.addEventListener('click', function () {
                    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'q', ctrlKey: true, bubbles: true }));
                });
            }
        })();
    </script>
</body>
</html>
