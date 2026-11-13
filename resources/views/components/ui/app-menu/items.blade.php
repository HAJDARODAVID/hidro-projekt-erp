{{-- One level of the app menu component (ui.app-menu), included recursively for the children of a group --}}
<ul @if ($depth > 1) class="app-menu-children" @endif>
    @foreach ($nodes as $node)
        @switch($node['type'])
            @case('link')
                <li class="app-menu-item">
                    <a
                        href="{{ $node['href'] }}"
                        @class(['app-menu-link', 'is-current' => $node['current']])
                        @if ($node['current']) aria-current="page" @endif
                        @if ($node['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                        @if (!$interactive) onclick="return false;" @endif
                    >
                        @if ($node['icon'])
                            <i class="bi bi-{{ $node['icon'] }} app-menu-icon"></i>
                        @elseif ($depth > 1)
                            <span class="app-menu-bullet">&bull;</span>
                        @endif
                        <span class="app-menu-title">{{ $node['title'] }}</span>
                        @if ($node['new_tab']) <i class="bi bi-box-arrow-up-right small opacity-50"></i> @endif
                    </a>
                </li>
                @break

            @case('group')
                {{-- Open: contains the current page, else the state the user left it in (per browser) --}}
                <li class="app-menu-item app-menu-group"
                    x-data="{
                        key: '{{ $uid }}-{{ $node['id'] }}',
                        open: {{ $node['open'] ? 'true' : 'false' }},
                        init() { if (!this.open) this.open = localStorage.getItem(this.key) === '1'; },
                        toggle() { this.open = !this.open; localStorage.setItem(this.key, this.open ? '1' : '0'); },
                    }"
                >
                    <button
                        type="button"
                        @class(['app-menu-group-toggle', 'has-current' => $node['open']])
                        x-on:click="toggle()"
                        :aria-expanded="open.toString()"
                        aria-expanded="{{ $node['open'] ? 'true' : 'false' }}"
                    >
                        @if ($node['icon'])
                            <i class="bi bi-{{ $node['icon'] }} app-menu-icon"></i>
                        @elseif ($depth > 1)
                            <span class="app-menu-bullet">&bull;</span>
                        @endif
                        <span class="app-menu-title">{{ $node['title'] }}</span>
                        <i class="bi bi-chevron-right app-menu-chevron"></i>
                    </button>
                    <div x-show="open" @if (!$node['open']) style="display: none" @endif>
                        @include('components.ui.app-menu.items', ['nodes' => $node['children'], 'depth' => $depth + 1])
                    </div>
                </li>
                @break

            @case('header')
                <li class="app-menu-header">{{ $node['title'] }}</li>
                @break

            @case('divider')
                <li class="app-menu-divider" role="separator"></li>
                @break
        @endswitch
    @endforeach
</ul>
