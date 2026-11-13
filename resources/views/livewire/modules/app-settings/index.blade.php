<div class="px-3 flex-fill d-flex flex-column" id="module_container"
    x-data="{
        dragId: null,
        overId: null,
        rowUnit: {{ \App\Services\Application\Dashboard\DashboardWidgetRegistry::ROW_UNIT }},
        minRows: {{ \App\Services\Application\Dashboard\DashboardWidgetRegistry::FULL_MIN_ROWS }},
        {{-- Stretch every 'Full' height tile to the bottom of the screen.
             Rows are rounded DOWN, so the tile (incl. its bottom gap) never reaches past the screen bottom. --}}
        fitFullTiles() {
            const grid = this.$root.querySelector('.app-dash-grid');
            if (!grid) return;
            const tiles = grid.querySelectorAll('.app-dash-tile[data-full-height]');
            if (!tiles.length) return;

            {{-- clientHeight = visible area without a (horizontal) scrollbar; innerHeight would include it --}}
            const viewport = () => document.documentElement.clientHeight;
            const areaBottom = el => el.getBoundingClientRect().bottom + parseFloat(getComputedStyle(el).marginBottom || 0);
            let shrink = 0;

            {{-- A few passes: size the tiles, then correct for whatever below the grid still makes the page scroll (layout padding etc.) --}}
            for (let pass = 0; pass < 3; pass++) {
                tiles.forEach(el => {
                    const top = el.getBoundingClientRect().top + window.scrollY;
                    const rows = Math.max(this.minRows, Math.floor((viewport() - top - shrink) / this.rowUnit));
                    el.style.gridRow = 'span ' + rows;
                });

                {{-- Another (normal) tile reaches lower than the full ones: scrolling is legit, don't squash the full tiles --}}
                const contentBottom = Math.max(...Array.from(grid.children).map(areaBottom));
                const fullBottom = Math.max(...Array.from(tiles).map(areaBottom));
                if (contentBottom > fullBottom + 1) break;

                const overflow = document.documentElement.scrollHeight - viewport();
                if (overflow <= 0) break;
                shrink += overflow;
            }
        },
        init() {
            let queued = false;
            const fit = () => {
                if (queued) return;
                queued = true;
                requestAnimationFrame(() => { queued = false; this.fitFullTiles(); });
            };
            fit();
            window.addEventListener('resize', fit);
            window.addEventListener('load', fit);
            {{-- Re-fit whenever the page height changes later (CSS/fonts loading, header, widget content...).
                 The fit is idempotent, so its own changes settle after one extra run. --}}
            const ro = new ResizeObserver(fit);
            ro.observe(document.body);
            if (this.$root.parentElement) ro.observe(this.$root.parentElement);
            {{-- Livewire morph resets the inline styles, so re-fit after every update of this component --}}
            Livewire.hook('morphed', ({ component }) => { if (component.id === this.$wire.$id) fit(); });
        },
    }">
    <style>
        {{-- Small rows and no row-gap: the vertical space between tiles is each tile's margin-bottom (inside its own rows) --}}
        .app-dash-grid { display: grid; grid-template-columns: repeat({{ \App\Services\Application\Dashboard\DashboardWidgetRegistry::COLUMNS }}, minmax(0, 1fr)); grid-auto-rows: {{ \App\Services\Application\Dashboard\DashboardWidgetRegistry::ROW_UNIT }}px; grid-auto-flow: row dense; column-gap: 1rem; row-gap: 0; }
        .app-dash-tile { margin-bottom: {{ \App\Services\Application\Dashboard\DashboardWidgetRegistry::TILE_GAP }}px; display: flex; flex-direction: column; min-height: 0; background: #fff; border: 1px solid var(--bs-border-color); transition: box-shadow .15s, outline-color .15s; }
        .app-dash-tile > .tile-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .5rem .75rem; border-bottom: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); }
        .app-dash-tile > .tile-body { flex: 1 1 auto; min-height: 0; overflow: auto; position: relative; }
        .app-dash-grid.editing .app-dash-tile { outline: 2px dashed var(--bs-border-color); outline-offset: 2px; }
        .app-dash-grid.editing .tile-header { cursor: grab; }
        .app-dash-tile.drag-over { outline-color: var(--bs-primary) !important; box-shadow: 0 0 0 .25rem rgba(var(--bs-primary-rgb), .15); }
        .app-dash-tile .tile-body.editing { pointer-events: none; opacity: .6; }
        .app-dash-empty { grid-column: 1 / -1; grid-row: span 30; border: 2px dashed var(--bs-border-color); display: flex; align-items: center; justify-content: center; flex-direction: column; color: var(--bs-secondary-color); }
        .app-dash-catalog-item { cursor: pointer; transition: background .1s; }
        .app-dash-catalog-item:hover { background: var(--bs-tertiary-bg); }
        .app-dash-fab-group { position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1030; display: flex; gap: .5rem; }
        .app-dash-layout-menu { position: absolute; right: 0; bottom: calc(100% + .5rem); width: 300px; max-height: 60vh; overflow-y: auto; }
        .app-dash-layout-item { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .35rem 1rem; border: 0; background: none; font-size: .875rem; }
        .app-dash-layout-item:hover { background: var(--bs-tertiary-bg); }
        [x-cloak] { display: none !important; }
        .app-dash-fab { width: 44px; height: 44px; border-radius: 50% !important; display: flex; align-items: center; justify-content: center; opacity: .75; transition: opacity .15s, transform .15s; }
        .app-dash-fab:hover { opacity: 1; transform: scale(1.08); }
        @media (max-width: 991.98px) { .app-dash-tile { grid-column: 1 / -1 !important; } }
    </style>

    {{-- Separate wire:keys so Livewire doesn't morph the "Reset" button into "Customize" (it would keep the reset click handler) --}}
    @if ($editMode)
        {{-- Edit toolbar (only takes space while editing) --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 mb-3" wire:key="dash-toolbar-edit">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm" style="width: 220px" maxlength="100"
                    wire:model.blur="layoutName" placeholder="{{ translator('Layout name') }}" title="{{ translator('Layout name') }}" />
                <div class="btn-group btn-group-sm" role="group" aria-label="{{ translator('Visibility') }}">
                    <button type="button" class="btn shadow {{ $visibility === 'private' ? 'btn-secondary' : 'btn-light border' }}" style="border-radius: 0"
                        wire:click="setVisibility('private')" title="{{ translator('Only you can see this layout') }}">
                        <i class="bi bi-lock"></i> {{ translator('Private') }}
                    </button>
                    <button type="button" class="btn shadow {{ $visibility === 'global' ? 'btn-secondary' : 'btn-light border' }}" style="border-radius: 0"
                        wire:click="setVisibility('global')" title="{{ translator('Everybody can use this layout (only you can change it)') }}">
                        <i class="bi bi-globe"></i> {{ translator('Global') }}
                    </button>
                </div>
                <x-ui.btn type="lig.sm" icon="trash" action="deleteLayout" title="{{ translator('Delete layout') }}"
                    wire:confirm="{{ translator('Delete this layout?') }}" />
                <i class="bi bi-info-circle text-muted" title="{{ translator('Drag tiles by their header to reorder them. Use the controls to resize, configure or remove.') }}"></i>
            </div>
            <div class="d-flex gap-2">
                <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" action="resetLayout" text="{{ translator('Reset') }}"
                    wire:confirm="{{ translator('Reset the dashboard to the default layout?') }}" />
                <x-ui.btn type="lig.sm" icon="plus-lg" action="toggleCatalog" text="{{ translator('Add widget') }}" />
                <x-ui.btn type="pri.sm" icon="check-lg" action="toggleEditMode" text="{{ translator('Done') }}" />
            </div>
        </div>
    @else
        {{-- Floating layout picker + customize button (takes no space) --}}
        <div class="app-dash-fab-group" wire:key="dash-toolbar-view" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
            <div class="app-dash-layout-menu shadow border bg-white" x-show="open" x-cloak x-transition.opacity>
                <div class="px-3 pt-2 pb-1 text-muted text-uppercase fw-semibold" style="font-size: .7rem">{{ translator('My layouts') }}</div>
                @forelse ($myLayouts as $l)
                    <button type="button" class="app-dash-layout-item" wire:click="selectLayout({{ $l->id }})" x-on:click="open = false">
                        <i class="bi bi-check2 {{ $l->id === $dashboardId ? '' : 'invisible' }}"></i>
                        <span class="text-truncate flex-fill text-start">{{ $l->name }}</span>
                        <i class="bi bi-{{ $l->isGlobal() ? 'globe' : 'lock' }} text-muted" title="{{ $l->isGlobal() ? translator('Global') : translator('Private') }}"></i>
                    </button>
                @empty
                    <div class="px-3 pb-1 small text-muted">{{ translator('No own layouts yet.') }}</div>
                @endforelse

                @if ($sharedLayouts->count())
                    <div class="px-3 pt-2 pb-1 text-muted text-uppercase fw-semibold border-top mt-1" style="font-size: .7rem">{{ translator('Global layouts') }}</div>
                    @foreach ($sharedLayouts as $l)
                        <button type="button" class="app-dash-layout-item" wire:click="selectLayout({{ $l->id }})" x-on:click="open = false">
                            <i class="bi bi-check2 {{ $l->id === $dashboardId ? '' : 'invisible' }}"></i>
                            <span class="text-truncate flex-fill text-start">{{ $l->name }}</span>
                            <span class="small text-muted text-truncate" style="max-width: 110px">{{ $l->user?->name }}</span>
                        </button>
                    @endforeach
                @endif

                <div class="border-top mt-1 py-1">
                    <button type="button" class="app-dash-layout-item" wire:click="createLayout" x-on:click="open = false">
                        <i class="bi bi-plus-lg"></i><span class="flex-fill text-start">{{ translator('New layout') }}</span>
                    </button>
                    <button type="button" class="app-dash-layout-item" wire:click="duplicateLayout" x-on:click="open = false">
                        <i class="bi bi-copy"></i><span class="flex-fill text-start">{{ translator('Duplicate current layout') }}</span>
                    </button>
                </div>
            </div>

            <button type="button" class="btn btn-light border shadow app-dash-fab" x-on:click="open = !open" title="{{ translator('Layouts') }}: {{ $layoutName }}">
                <i class="bi bi-layers"></i>
            </button>
            <button type="button" class="btn btn-light border shadow app-dash-fab" wire:click="toggleEditMode"
                title="{{ $isOwner ? translator('Customize dashboard') : translator('Customize a private copy of this layout') }}">
                <i class="bi bi-grid-1x2"></i>
            </button>
        </div>
    @endif

    {{-- Widget catalog --}}
    @if ($editMode && $showCatalog)
        <x-ui.card title="{{ translator('Add widget') }}" class="mb-3" wire:key="dash-catalog">
            <x-slot:headerActions>
                <x-ui.btn type="lig.sm" icon="x-lg" action="toggleCatalog" />
            </x-slot:headerActions>
            <div class="row g-2">
                @foreach ($catalog as $key => $widget)
                    <div class="col-md-4 col-lg-3">
                        <div class="border p-3 h-100 app-dash-catalog-item" wire:click="addWidget('{{ $key }}')">
                            <div class="d-flex align-items-center gap-2 fw-semibold">
                                <x-ui.bi-icon :icon="$widget['icon']" /> {{ translator($widget['title']) }}
                            </div>
                            <div class="small text-muted mt-1">{{ translator($widget['description']) }}</div>
                            <div class="small text-muted mt-2">
                                {{ translator('Default size') }}: {{ $widths[$widget['w']] }} &times; {{ $heights[$widget['h']] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Tile grid --}}
    <div class="app-dash-grid @if($editMode) editing @endif" wire:key="dash-grid">
        @forelse ($layout as $i => $tile)
            @php
                $widget = $catalog[$tile['widget']];
                $isFull = $tile['h'] == \App\Services\Application\Dashboard\DashboardWidgetRegistry::HEIGHT_FULL;
            @endphp
            {{-- Full height tiles: fallback rows until fitFullTiles() sets the real span --}}
            <div class="app-dash-tile shadow-sm"
                wire:key="tile-{{ $tile['id'] }}"
                style="grid-column: span {{ $tile['w'] }}; grid-row: span {{ $isFull ? \App\Services\Application\Dashboard\DashboardWidgetRegistry::FULL_FALLBACK_ROWS : $tile['h'] }};"
                @if ($isFull) data-full-height @endif
                @if ($editMode)
                    draggable="true"
                    x-on:dragstart="dragId = '{{ $tile['id'] }}'; $event.dataTransfer.effectAllowed = 'move'"
                    x-on:dragend="dragId = null; overId = null"
                    x-on:dragover.prevent="overId = '{{ $tile['id'] }}'"
                    x-on:dragleave="if (overId === '{{ $tile['id'] }}') overId = null"
                    x-on:drop.prevent="if (dragId && dragId !== '{{ $tile['id'] }}') $wire.dropTile(dragId, '{{ $tile['id'] }}'); dragId = null; overId = null"
                    x-bind:class="{ 'drag-over': overId === '{{ $tile['id'] }}' && dragId !== '{{ $tile['id'] }}' }"
                @endif
            >
                <div class="tile-header">
                    <div class="d-flex align-items-center gap-2 text-truncate fw-semibold" style="min-width: 0">
                        @if ($editMode) <i class="bi bi-grip-vertical text-muted"></i> @endif
                        <x-ui.bi-icon :icon="$widget['icon']" />
                        <span class="text-truncate">{{ translator($tile['title']) }}</span>
                    </div>

                    @if ($editMode)
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <select class="form-select form-select-sm" style="width: auto" title="{{ translator('Width') }}"
                                wire:change="resizeTile('{{ $tile['id'] }}', 'w', $event.target.value)">
                                @foreach ($widths as $val => $label)
                                    <option value="{{ $val }}" @selected($tile['w'] == $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" style="width: auto" title="{{ translator('Height') }}"
                                wire:change="resizeTile('{{ $tile['id'] }}', 'h', $event.target.value)">
                                @foreach ($heights as $val => $label)
                                    <option value="{{ $val }}" @selected($tile['h'] == $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.btn type="lig.sm" icon="chevron-left" wire:click="moveTile('{{ $tile['id'] }}', -1)" :disabled="$i === 0" title="{{ translator('Move back') }}" />
                            <x-ui.btn type="lig.sm" icon="chevron-right" wire:click="moveTile('{{ $tile['id'] }}', 1)" :disabled="$loop->last" title="{{ translator('Move forward') }}" />
                            <x-ui.btn type="lig.sm" icon="gear" action="openConfig" param="{{ $tile['id'] }}" title="{{ translator('Settings') }}" />
                            <x-ui.btn type="lig.sm" icon="trash" action="removeTile" param="{{ $tile['id'] }}" title="{{ translator('Remove') }}"
                                wire:confirm="{{ translator('Remove this widget from the dashboard?') }}" />
                        </div>
                    @endif
                </div>

                <div class="tile-body @if($editMode) editing @endif">
                    @livewire($widget['component'], ['tileId' => $tile['id'], 'settings' => $tile['settings']], key($tile['id'] . '-' . md5(json_encode($tile['settings']))))
                </div>
            </div>
        @empty
            <div class="app-dash-empty">
                <i class="bi bi-grid-3x3-gap fs-1"></i>
                <div class="mt-2">{{ translator('The dashboard is empty.') }}</div>
                <div class="mt-3">
                    <x-ui.btn type="pri.sm" icon="plus-lg" wire:click="toggleEditMode" text="{{ translator('Add widget') }}" />
                </div>
            </div>
        @endforelse
    </div>

    {{-- Tile settings panel --}}
    @if ($configTile)
        @php $schema = $catalog[$configTile['widget']]['settings'] ?? []; @endphp
        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
            style="background: rgba(0,0,0,.35); z-index: 1060;" wire:click.self="closeConfig" x-on:keydown.escape.window="$wire.closeConfig()">
            <div class="bg-white shadow" style="width: 420px; max-width: 95vw;">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div class="fw-semibold"><x-ui.bi-icon icon="gear" /> {{ translator('Widget settings') }}</div>
                    <x-ui.btn type="lig.sm" icon="x-lg" action="closeConfig" />
                </div>
                <form class="p-3 d-flex flex-column gap-3" wire:submit="saveConfig">
                    <div>
                        <label class="form-label small mb-1">{{ translator('Title') }}</label>
                        <input type="text" class="form-control form-control-sm" wire:model="configForm.title" />
                    </div>
                    @foreach ($schema as $key => $def)
                        <div>
                            <label class="form-label small mb-1">{{ translator($def['label']) }}</label>
                            @if ($def['type'] === 'select')
                                <select class="form-select form-select-sm" wire:model="configForm.{{ $key }}">
                                    @foreach ($def['options'] as $val => $label)
                                        <option value="{{ $val }}">{{ translator($label) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($def['type'] === 'number')
                                <input type="number" min="1" max="100" class="form-control form-control-sm" wire:model="configForm.{{ $key }}" />
                            @else
                                <input type="text" class="form-control form-control-sm" wire:model="configForm.{{ $key }}" />
                            @endif
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <x-ui.btn type="lig.sm" action="closeConfig" text="{{ translator('Cancel') }}" />
                        <button type="submit" class="btn btn-primary btn-sm shadow" style="border-radius: 0">{{ translator('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
