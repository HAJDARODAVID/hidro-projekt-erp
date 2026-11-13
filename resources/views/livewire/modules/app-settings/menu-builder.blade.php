<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <style>
        .mb-scroll { overflow-y: auto; max-height: calc(100vh - 290px); min-height: 300px; }
        .mb-palette-btn { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .3rem .5rem; border: 1px solid transparent; background: none; text-align: left; font-size: .85rem; }
        .mb-palette-btn:hover { background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.08); }
        .mb-palette-group { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #8a94a0; padding: .6rem .5rem .2rem; display: flex; align-items: center; }
        .mb-row { display: flex; align-items: center; gap: .5rem; padding: .35rem .5rem; border: 1px solid #e3e6ea; background: #fff; margin-bottom: 4px; cursor: pointer; position: relative; min-height: 38px; }
        .mb-row:hover { border-color: #b9c0c8; }
        .mb-row.is-selected { border-color: #0d6efd; box-shadow: 0 0 0 1px #0d6efd inset; }
        .mb-row.is-inactive > .mb-row-main { opacity: .45; }
        .mb-row.is-dragging { opacity: .35; }
        .mb-row.drop-before::before,
        .mb-row.drop-after::after { content: ''; position: absolute; left: 0; right: 0; height: 3px; background: #0d6efd; }
        .mb-row.drop-before::before { top: -4px; }
        .mb-row.drop-after::after { bottom: -4px; }
        .mb-row.drop-inside { background: rgba(13,110,253,.08); border-color: #0d6efd; border-style: dashed; }
        .mb-row-main { display: flex; align-items: center; gap: .5rem; min-width: 0; flex: 1; }
        .mb-handle { cursor: grab; color: #adb5bd; }
        .mb-row-title { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mb-row-sub { font-size: .75rem; color: #8a94a0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mb-row-actions { display: flex; gap: 0; opacity: 0; transition: opacity .1s; }
        .mb-row:hover .mb-row-actions,
        .mb-row.is-selected .mb-row-actions { opacity: 1; }
        .mb-row-actions button { border: 0; background: none; padding: .1rem .3rem; color: #6c757d; }
        .mb-row-actions button:hover { color: #212529; background: rgba(0,0,0,.06); }
        .mb-row-actions button:disabled { opacity: .3; }
        .mb-children { margin-left: 1.6rem; padding-left: .6rem; border-left: 2px solid #e9ecef; }
        .mb-children-empty { font-size: .75rem; color: #adb5bd; font-style: italic; padding: .2rem .5rem .5rem; }
        .mb-drop-end { border: 1px dashed #ced4da; color: #8a94a0; font-size: .8rem; text-align: center; padding: .5rem; margin-top: .5rem; }
        .mb-drop-end.is-over { border-color: #0d6efd; color: #0d6efd; background: rgba(13,110,253,.06); }
        .mb-icon-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; }
        .mb-icon-grid button { border: 1px solid transparent; background: none; padding: .3rem 0; }
        .mb-icon-grid button:hover, .mb-icon-grid button.is-picked { border-color: #0d6efd; color: #0d6efd; }
        .mb-preview { background: #f8f9fa; max-width: 300px; padding: .5rem 0; }
    </style>

    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        {{-- Toolbar: menu selection and menu settings --}}
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
            <b>{{ translator('Menu builder') }}</b>
            <x-v-divider class="align-self-stretch" />

            @if ($menus->isNotEmpty())
                <select class="form-select form-select-sm" style="width: auto; min-width: 180px" wire:change="selectMenu($event.target.value)" title="{{ translator('Menu') }}">
                    @foreach ($menus as $m)
                        <option value="{{ $m->id }}" @selected($menu && $m->id === $menu->id)>{{ $m->name }} ({{ $m->key }})@if (!$m->active) – {{ translator('off') }}@endif</option>
                    @endforeach
                </select>
            @endif
            <x-ui.btn type="lig.sm" icon="plus-lg" text="{{ translator('New menu') }}" action="toggleNewMenu" />

            @if ($menu)
                <x-v-divider class="align-self-stretch" />
                <div class="input-group input-group-sm" style="width: 220px">
                    <span class="input-group-text">{{ translator('Name') }}</span>
                    <input type="text" class="form-control" wire:model.blur="menuForm.name" maxlength="100">
                </div>
                <div class="input-group input-group-sm" style="width: 180px" title="{{ translator('Used in the code') }}: &lt;x-ui.app-menu menu=&quot;{{ $menu->key }}&quot; /&gt;">
                    <span class="input-group-text">{{ translator('Key') }}</span>
                    <input type="text" class="form-control font-monospace" wire:model.blur="menuForm.key" maxlength="50">
                </div>
                <div class="form-check form-switch mb-0 ms-1" title="{{ translator('A switched off menu is not rendered anywhere') }}">
                    <input class="form-check-input" type="checkbox" id="mb-menu-active" wire:model.live="menuForm.active">
                    <label class="form-check-label small" for="mb-menu-active">{{ translator('Active') }}</label>
                </div>
                <x-ui.btn
                    type="lig.sm"
                    icon="trash"
                    action="deleteMenu"
                    title="{{ translator('Delete menu') }}"
                    wire:confirm="{{ translator('Delete the menu with all its items? Places that render it show nothing anymore.') }}"
                />
            @endif

            <div class="ms-auto d-flex gap-2">
                @if ($menu && $hasLegacyMenu)
                    <x-ui.btn
                        type="lig.sm"
                        icon="box-arrow-in-down"
                        text="{{ translator('Import current sidebar') }}"
                        action="importLegacyMenu"
                        title="{{ translator('Copy the groups and links of the current sidebar to the end of this menu') }}"
                        wire:confirm="{{ translator('Copy the groups and links of the current sidebar (module items) to the end of this menu?') }}"
                        wire:loading.attr="disabled"
                        wire:target="importLegacyMenu"
                    />
                @endif
                {{-- Local: export the menus into the sync file / other envs: install the sync file --}}
                @if ($isLocalEnv)
                    <x-ui.btn
                        type="lig.sm"
                        icon="box-arrow-up"
                        action="exportMenus"
                        title="{{ translator('Export the menus to the sync file') }}"
                        wire:loading.attr="disabled"
                        wire:target="exportMenus"
                    />
                @else
                    <x-ui.btn
                        type="lig.sm"
                        icon="arrow-repeat"
                        action="syncMenusFromFile"
                        :disabled="$syncFileDate === NULL"
                        title="{{ $syncFileDate ? translator('Sync the menus from the file') . ' (' . $syncFileDate . ')' : translator('The sync file does not exist') }}"
                        wire:confirm="{{ translator('Sync the menus from the file? Every menu in the file gets exactly the items of the file, changes made here are lost.') }}"
                        wire:loading.attr="disabled"
                        wire:target="syncMenusFromFile"
                    />
                @endif
            </div>
        </div>

        {{-- New menu --}}
        @if ($showNewMenu)
            <form class="d-flex align-items-center gap-2 mb-3 p-2 border bg-light" wire:submit="createMenu" wire:key="mb-new-menu">
                <i class="bi bi-plus-square text-primary"></i>
                <input type="text" class="form-control form-control-sm" style="width: 220px" placeholder="{{ translator('Name, e.g. Main menu') }}" wire:model="newMenu.name" maxlength="100" autofocus>
                <input type="text" class="form-control form-control-sm font-monospace" style="width: 180px" placeholder="{{ translator('Key, e.g. main') }}" wire:model="newMenu.key" maxlength="50">
                <button type="submit" class="btn btn-primary btn-sm shadow" style="border-radius: 0">{{ translator('Create') }}</button>
                <button type="button" class="btn btn-light btn-sm shadow gray-border-color" style="border-radius: 0" wire:click="toggleNewMenu">{{ translator('Cancel') }}</button>
                <span class="small text-muted ms-2">{{ translator('The key is used in the code, the sidebar uses the key') }} <code>main</code>.</span>
            </form>
        @endif

        @if (!$menu)
            <div class="flex-fill d-flex flex-column align-items-center justify-content-center text-center text-muted py-5">
                <i class="bi bi-menu-button-wide fs-1 mb-2"></i>
                <div class="mb-3">{{ translator('No menu yet. Create one, e.g. the sidebar menu with the key') }} <code>main</code>.</div>
                @if (!$showNewMenu)
                    <x-ui.btn type="pri.sm" icon="plus-lg" text="{{ translator('New menu') }}" action="toggleNewMenu" />
                @endif
            </div>
        @else
            <div class="row g-3 flex-fill">
                {{-- ====================== Add items ====================== --}}
                <div class="col-xl-3 col-lg-4 d-flex flex-column">
                    <x-ui.card title="{{ translator('Add items') }}" class="flex-fill d-flex flex-column">
                        <div class="small text-muted mb-2">
                            @if ($selected && $selected->isGroup())
                                <i class="bi bi-folder2-open"></i> {{ translator('Adds into the group') }} <b>{{ $selected->title }}</b>
                            @elseif ($selected)
                                <i class="bi bi-arrow-return-right"></i> {{ translator('Adds after') }} <b>{{ $selected->title ?? translator('Divider') }}</b>
                            @else
                                <i class="bi bi-arrow-down"></i> {{ translator('Adds at the end of the menu. Select an item to add into / after it.') }}
                            @endif
                        </div>
                        <div class="d-grid gap-1 mb-2" style="grid-template-columns: 1fr 1fr">
                            @foreach ($types as $typeKey => $type)
                                <button type="button" class="btn btn-light btn-sm gray-border-color text-start" style="border-radius: 0" wire:click="addItem('{{ $typeKey }}')">
                                    <i class="bi bi-{{ $type['icon'] }} me-1"></i> {{ translator($type['label']) }}
                                </button>
                            @endforeach
                        </div>

                        <div x-data="{ q: '' }" class="d-flex flex-column flex-fill" style="min-height: 0">
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control" placeholder="{{ translator('Search routes and modules') }}" x-model="q">
                            </div>
                            <div class="mb-scroll" style="max-height: calc(100vh - 420px)">
                                {{-- Whole modules: group + all its routes --}}
                                <div class="mb-palette-group">{{ translator('Modules') }}</div>
                                @foreach ($appModules as $appModule)
                                    <button
                                        type="button"
                                        class="mb-palette-btn"
                                        wire:click="addModule(@js($appModule->id))"
                                        data-search="{{ mb_strtolower($appModule->name) }}"
                                        x-show="!q || $el.dataset.search.includes(q.toLowerCase())"
                                        title="{{ translator('Add a group with all the routes of the module') }}"
                                    >
                                        <i class="bi bi-folder-plus text-primary"></i>
                                        <span class="flex-fill text-truncate">{{ $appModule->name }}</span>
                                        @if (!$appModule->active) <span class="badge text-bg-secondary">{{ translator('off') }}</span> @endif
                                    </button>
                                @endforeach

                                {{-- Single routes --}}
                                @foreach ($catalog as $group)
                                    @php
                                        $groupSearch = mb_strtolower(collect($group['routes'])->map(fn ($r) => $r['title'] . ' ' . $r['name'] . ' ' . $r['uri'])->implode(' ') . ' ' . $group['module']);
                                    @endphp
                                    <div data-search="{{ $groupSearch }}" x-show="!q || $el.dataset.search.includes(q.toLowerCase())">
                                        <div class="mb-palette-group">
                                            <span class="text-truncate">{{ $group['module'] ?? translator('Other routes') }}</span>
                                        </div>
                                        @foreach ($group['routes'] as $route)
                                            <button
                                                type="button"
                                                class="mb-palette-btn"
                                                wire:click="addRoute(@js($route['name']))"
                                                data-search="{{ mb_strtolower($route['title'] . ' ' . $route['name'] . ' ' . $route['uri'] . ' ' . $group['module']) }}"
                                                x-show="!q || $el.dataset.search.includes(q.toLowerCase())"
                                                title="{{ $route['uri'] }}"
                                            >
                                                <i class="bi bi-plus-lg text-primary"></i>
                                                <span class="d-flex flex-column flex-fill" style="min-width: 0">
                                                    <span class="text-truncate">{{ $route['title'] }}</span>
                                                    <span class="text-truncate text-muted font-monospace" style="font-size: .7rem">{{ $route['name'] }}</span>
                                                </span>
                                                @if (!$route['active']) <span class="badge text-bg-secondary">{{ translator('off') }}</span> @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- ====================== Structure (drag & drop) ====================== --}}
                <div class="col-xl col-lg-8 d-flex flex-column">
                    <x-ui.card class="flex-fill d-flex flex-column">
                        <x-slot:title>
                            {{ translator('Structure') }}
                            <span class="small text-muted ms-2">{{ translator('Drag to move, drop on a group to put it inside. Max.') }} {{ $maxDepth }} {{ translator('levels') }}.</span>
                        </x-slot:title>
                        <div
                            class="mb-scroll"
                            x-data="{
                                dragId: null, overId: null, zone: null, overEnd: false,
                                start(e, id) {
                                    this.dragId = id;
                                    e.dataTransfer.effectAllowed = 'move';
                                    e.dataTransfer.setData('text/plain', String(id));
                                },
                                over(e, id, isGroup) {
                                    if (this.dragId === null || this.dragId === id) return;
                                    e.preventDefault();
                                    const r = e.currentTarget.getBoundingClientRect();
                                    const y = (e.clientY - r.top) / r.height;
                                    this.zone = isGroup ? (y < .3 ? 'before' : (y > .7 ? 'after' : 'inside')) : (y < .5 ? 'before' : 'after');
                                    this.overId = id;
                                    this.overEnd = false;
                                },
                                drop(e, id) {
                                    e.preventDefault();
                                    if (this.dragId !== null && this.dragId !== id && this.zone) $wire.moveItem(this.dragId, id, this.zone);
                                    this.end();
                                },
                                dropEnd(e) {
                                    e.preventDefault();
                                    if (this.dragId !== null) $wire.moveItem(this.dragId, null, 'after');
                                    this.end();
                                },
                                end() { this.dragId = null; this.overId = null; this.zone = null; this.overEnd = false; },
                            }"
                            x-on:dragend.window="end()"
                        >
                            @if (count($tree))
                                @include('livewire.modules.app-settings.menu-builder.tree', ['nodes' => $tree, 'parentId' => NULL])
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
                                    {{ translator('The menu is empty. Add groups and links from the left') }}@if ($hasLegacyMenu), {{ translator('or import the current sidebar') }}@endif.
                                </div>
                            @endif

                            <div
                                class="mb-drop-end"
                                :class="{ 'is-over': overEnd }"
                                x-show="dragId !== null"
                                style="display: none"
                                x-on:dragover.prevent="overEnd = true; overId = null; zone = null"
                                x-on:dragleave="overEnd = false"
                                x-on:drop="dropEnd($event)"
                            >
                                <i class="bi bi-arrow-bar-down"></i> {{ translator('Drop here to move it to the end of the menu') }}
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- ====================== Settings / Preview ====================== --}}
                <div class="col-xl-3 col-lg-12 d-flex flex-column">
                    <x-ui.card class="flex-fill d-flex flex-column">
                        <x-slot:title>
                            <ul class="nav nav-pills nav-sm gap-1" style="font-size: .85rem">
                                <li class="nav-item">
                                    <a href="#" @class(['nav-link py-1 px-2', 'active' => $panel === 'settings']) style="border-radius: 0" wire:click.prevent="$set('panel', 'settings')">
                                        <i class="bi bi-sliders"></i> {{ translator('Item settings') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" @class(['nav-link py-1 px-2', 'active' => $panel === 'preview']) style="border-radius: 0" wire:click.prevent="$set('panel', 'preview')">
                                        <i class="bi bi-eye"></i> {{ translator('Preview') }}
                                    </a>
                                </li>
                            </ul>
                        </x-slot:title>

                        <div class="mb-scroll">
                            @if ($panel === 'preview')
                                @include('livewire.modules.app-settings.menu-builder.preview')
                            @elseif ($selected)
                                @include('livewire.modules.app-settings.menu-builder.settings')
                            @else
                                <div class="text-muted small">
                                    <p><i class="bi bi-hand-index"></i> {{ translator('Select an item in the structure to edit it.') }}</p>
                                    <ul class="ps-3 mb-0">
                                        <li class="mb-1"><b>{{ translator('Group') }}</b> – {{ translator('collapsible, holds links and other groups. Hidden when the user can see no link inside.') }}</li>
                                        <li class="mb-1"><b>{{ translator('Link') }}</b> – {{ translator('a route or an URL. Hidden when the user may not open the route (ACL).') }}</li>
                                        <li class="mb-1"><b>{{ translator('Header') }}</b> – {{ translator('a small caption above the next items.') }}</li>
                                        <li class="mb-1"><b>{{ translator('Divider') }}</b> – {{ translator('a line between items.') }}</li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Datalists for the settings inputs --}}
    <datalist id="mb-routes">
        @foreach ($linkableRoutes as $name => $uri)
            <option value="{{ $name }}">{{ $uri }}</option>
        @endforeach
    </datalist>
    <datalist id="mb-permissions">
        @foreach ($permissionKeys as $key => $name)
            <option value="{{ $key }}">{{ $name }}</option>
        @endforeach
    </datalist>
</div>
