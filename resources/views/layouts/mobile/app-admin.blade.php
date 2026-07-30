<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
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
        :root {
            --mobile-topbar-h: 56px;
            --mobile-bottombar-h: 60px;
            --mobile-accent: #0d6efd;
        }

        html, body {
            overscroll-behavior-y: contain;
            overflow-x: hidden;
        }

        body.mobile-admin {
            background: #f2f4f7;
            padding-top: var(--mobile-topbar-h);
            padding-bottom: calc(var(--mobile-bottombar-h) + env(safe-area-inset-bottom));
        }

        body.mobile-admin.drawer-open {
            overflow: hidden;
        }

        /* Top bar */
        .mobile-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--mobile-topbar-h);
            z-index: 1040;
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: 0 .5rem;
            background: #1b1e21;
            color: #fff;
            box-shadow: 0 1px 8px rgba(0,0,0,.15);
        }

        .mobile-topbar .btn-icon {
            background: transparent;
            border: 0;
            color: #fff;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.25rem;
        }

        .mobile-topbar .btn-icon:active {
            background: rgba(255,255,255,.12);
        }

        .mobile-topbar .mobile-title {
            font-weight: 700;
            font-size: 1rem;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Content */
        .mobile-content {
            min-height: calc(100vh - var(--mobile-topbar-h) - var(--mobile-bottombar-h));
            padding: 1rem;
        }

        /* Prevent taps on cards/links from being hijacked into a text-selection
           drag, which cancels the scroll gesture on real touch devices. */
        .mobile-content a,
        .mobile-content .card,
        .mobile-content .list-group-item-action {
            -webkit-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }

        /* Fixed bottom nav */
        .mobile-bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: calc(var(--mobile-bottombar-h) + env(safe-area-inset-bottom));
            padding-bottom: env(safe-area-inset-bottom);
            background: #fff;
            display: flex;
            z-index: 1040;
            box-shadow: 0 -1px 10px rgba(0,0,0,.08);
        }

        .mobile-bottom-nav a,
        .mobile-bottom-nav button {
            flex: 1;
            border: 0;
            background: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            font-size: .68rem;
            color: #6c757d;
            text-decoration: none;
        }

        .mobile-bottom-nav i {
            font-size: 1.3rem;
        }

        .mobile-bottom-nav .active,
        .mobile-bottom-nav a.active {
            color: var(--mobile-accent);
        }

        /* Swipeable side drawer */
        .mobile-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
            z-index: 1049;
        }

        .mobile-drawer-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-drawer {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: min(82vw, 320px);
            background: #fff;
            z-index: 1050;
            transform: translateX(-100%);
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 18px rgba(0,0,0,.2);
        }

        .mobile-drawer.dragging {
            transition: none;
        }

        .mobile-drawer:not(.dragging) {
            transition: transform .28s ease;
        }

        .mobile-drawer.open {
            transform: translateX(0);
        }

        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1rem .5rem 1rem;
            flex-shrink: 0;
        }

        .mobile-drawer-header strong {
            font-size: 1.05rem;
        }

        .mobile-drawer-body {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-drawer-footer {
            flex-shrink: 0;
            border-top: 1px solid #eee;
        }

        /* Edge zone used to detect swipe-to-open gesture from screen edge */
        .mobile-drawer-edge {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 18px;
            z-index: 1045;
        }
    </style>
</head>
<body class="mobile-admin h-100">

    @livewire('exception-modal')

    <div class="mobile-drawer-edge" id="mobileDrawerEdge"></div>
    <div class="mobile-drawer-backdrop" id="mobileDrawerBackdrop"></div>

    <header class="mobile-topbar">
        <div class="mobile-title">{{ config('app.name') }}</div>
    </header>

    <nav class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
        <div class="mobile-drawer-header">
            <strong>{{ config('app.name') }}</strong>
            <button type="button" class="btn-icon text-dark" id="mobileDrawerClose" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mobile-drawer-body">
            @livewire('main-menu')
        </div>
        <div class="mobile-drawer-footer">
            @livewire('my-profile-component')
        </div>
    </nav>

    <main class="mobile-content">
        @yield('content')
    </main>

    <nav class="mobile-bottom-nav">
        <a href="{{ route('home') }}" class="@if(Route::currentRouteName() == 'home') active @endif">
            <i class="bi bi-house"></i>
            <span>{{ __('Home') }}</span>
        </a>
        <button type="button" id="mobileQuickAccessBtnBottom">
            <i class="bi bi-search"></i>
            <span>{{ __('Search') }}</span>
        </button>
        <button type="button" id="mobileQuickAccessBtnBottom">
            <i class="bi bi-search"></i>
            <span>{{ __('Search') }}</span>
        </button>
        <button type="button" id="mobileMenuToggleBottom">
            <i class="bi bi-list"></i>
            <span>{{ __('Menu') }}</span>
        </button>
    </nav>

    <x-ui.notification />
    @include('components.ui.quick-access-modal')
    @include('components.ui.calculator-modal')
    @livewire('components.modal.global-modal')
    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
    <script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        (function () {
            const body = document.body;
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('mobileDrawerBackdrop');
            const edge = document.getElementById('mobileDrawerEdge');
            const openBtns = [document.getElementById('mobileMenuToggle'), document.getElementById('mobileMenuToggleBottom')];
            const closeBtn = document.getElementById('mobileDrawerClose');
            const quickAccessBtns = [document.getElementById('mobileQuickAccessBtn'), document.getElementById('mobileQuickAccessBtnBottom')];

            const drawerWidth = () => drawer.getBoundingClientRect().width;

            function openDrawer() {
                drawer.classList.add('open');
                backdrop.classList.add('open');
                body.classList.add('drawer-open');
                drawer.setAttribute('aria-hidden', 'false');
                openBtns.forEach((btn) => btn && btn.setAttribute('aria-expanded', 'true'));
            }

            function closeDrawer() {
                drawer.classList.remove('open');
                backdrop.classList.remove('open');
                body.classList.remove('drawer-open');
                drawer.setAttribute('aria-hidden', 'true');
                openBtns.forEach((btn) => btn && btn.setAttribute('aria-expanded', 'false'));
            }

            function toggleDrawer() {
                if (drawer.classList.contains('open')) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            }

            openBtns.forEach((btn) => btn && btn.addEventListener('click', toggleDrawer));
            closeBtn.addEventListener('click', closeDrawer);
            backdrop.addEventListener('click', closeDrawer);

            // Quick access modal is opened by its own Alpine component listening for Ctrl+Q.
            quickAccessBtns.forEach((btn) => {
                if (!btn) return;
                btn.addEventListener('click', () => {
                    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'q', ctrlKey: true, bubbles: true }));
                });
            });

            // --- Swipe gestures ---
            let touchStartX = null;
            let touchCurrentX = null;
            let draggingFromEdge = false;
            let draggingOpenDrawer = false;
            const SWIPE_OPEN_THRESHOLD = 45;
            const SWIPE_CLOSE_THRESHOLD = 45;

            function onTouchStart(e, fromEdge) {
                touchStartX = e.touches[0].clientX;
                touchCurrentX = touchStartX;
                draggingFromEdge = fromEdge;
                draggingOpenDrawer = drawer.classList.contains('open');
                drawer.classList.add('dragging');
            }

            function onTouchMove(e) {
                if (touchStartX === null) return;
                touchCurrentX = e.touches[0].clientX;
                const delta = touchCurrentX - touchStartX;

                if (draggingFromEdge && !draggingOpenDrawer) {
                    const x = Math.max(-drawerWidth(), Math.min(0, delta - drawerWidth()));
                    drawer.style.transform = `translateX(${x}px)`;
                    backdrop.classList.add('open');
                    backdrop.style.opacity = Math.min(1, Math.max(0, delta / drawerWidth()));
                } else if (draggingOpenDrawer && delta < 0) {
                    const x = Math.max(-drawerWidth(), delta);
                    drawer.style.transform = `translateX(${x}px)`;
                    backdrop.style.opacity = Math.min(1, Math.max(0, 1 + x / drawerWidth()));
                }
            }

            function onTouchEnd() {
                if (touchStartX === null) return;
                const delta = touchCurrentX - touchStartX;
                drawer.classList.remove('dragging');
                drawer.style.transform = '';
                backdrop.style.opacity = '';

                if (draggingFromEdge && !draggingOpenDrawer && delta > SWIPE_OPEN_THRESHOLD) {
                    openDrawer();
                } else if (draggingOpenDrawer && delta < -SWIPE_CLOSE_THRESHOLD) {
                    closeDrawer();
                } else if (draggingOpenDrawer) {
                    openDrawer();
                } else {
                    closeDrawer();
                }

                touchStartX = null;
                touchCurrentX = null;
            }

            edge.addEventListener('touchstart', (e) => onTouchStart(e, true), { passive: true });
            drawer.addEventListener('touchstart', (e) => onTouchStart(e, false), { passive: true });
            backdrop.addEventListener('touchstart', (e) => onTouchStart(e, false), { passive: true });

            [edge, drawer, backdrop].forEach((el) => {
                el.addEventListener('touchmove', onTouchMove, { passive: true });
                el.addEventListener('touchend', onTouchEnd);
                el.addEventListener('touchcancel', onTouchEnd);
            });

            // Close drawer automatically when navigating via a menu link (small screens).
            drawer.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (link) {
                    closeDrawer();
                }
            });
        })();
    </script>
</body>
</html>
