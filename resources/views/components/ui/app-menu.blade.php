{{-- Menu built with the menu builder. See App\View\Components\Ui\AppMenu --}}
@once
    <style>
        .app-menu { --app-menu-active-bg: rgba(13, 110, 253, .1); --app-menu-active-color: #0d6efd; --app-menu-hover-bg: rgba(0, 0, 0, .04); font-size: .9rem; }
        .app-menu ul { list-style: none; margin: 0; padding: 0; }
        .app-menu-link,
        .app-menu-group-toggle { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .4rem .75rem; color: #475563; text-decoration: none; background: none; border: 0; text-align: left; border-left: 3px solid transparent; }
        .app-menu-link:hover,
        .app-menu-group-toggle:hover { background: var(--app-menu-hover-bg); color: #212529; }
        .app-menu-link.is-current { background: var(--app-menu-active-bg); color: var(--app-menu-active-color); border-left-color: var(--app-menu-active-color); font-weight: 600; }
        .app-menu-group-toggle { font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
        .app-menu-group-toggle.has-current { color: #212529; }
        .app-menu-group-toggle .app-menu-chevron { margin-left: auto; font-size: .75rem; transition: transform .15s; }
        .app-menu-group-toggle[aria-expanded="true"] .app-menu-chevron { transform: rotate(90deg); }
        .app-menu-children .app-menu-link,
        .app-menu-children .app-menu-group-toggle { padding-left: 1.75rem; font-weight: normal; text-transform: none; letter-spacing: 0; }
        .app-menu-children .app-menu-children .app-menu-link { padding-left: 2.75rem; }
        .app-menu-children .app-menu-link.is-current { font-weight: 600; }
        .app-menu-icon { width: 1.1rem; text-align: center; flex-shrink: 0; }
        .app-menu-bullet { width: 1.1rem; text-align: center; flex-shrink: 0; opacity: .5; }
        .app-menu-header { padding: .9rem .75rem .25rem; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #8a94a0; }
        .app-menu-divider { margin: .4rem .75rem; border-top: 1px solid rgba(0, 0, 0, .1); }
        .app-menu-empty { padding: .75rem; color: #8a94a0; font-style: italic; }
        .app-menu-title { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
@endonce

<nav {{ $attributes->merge(['class' => 'app-menu']) }} aria-label="{{ translator('Main menu') }}">
    @if (count($items))
        @include('components.ui.app-menu.items', ['nodes' => $items, 'depth' => 1, 'uid' => $uid, 'interactive' => $interactive])
    @elseif ($preview)
        <div class="app-menu-empty">{{ translator('Nothing to show for this user.') }}</div>
    @endif
</nav>
