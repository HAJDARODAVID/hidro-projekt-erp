{{--
    Our toolbar of an AppDataTable (replaces the package toolbar, rendered in the package area before-tools).
    Our own markup for the search and the column select; the package parts with their own logic
    (filters, bulk actions, reorder, actions) are the package components, restyled in application.css.
    The rows per page are in our footer (livewire.tables.footer).

    Order:  [left-start] [search] [filters] [left-end] [actions] ......... [right-start] [actions] [bulk actions] [columns] [right-end]
--}}
@aware(['tableName', 'localisationPath'])

@if ($ownArea)
    @include($ownArea, $ownAreaParams)
@endif

@if ($this->showAppToolbar)
    {{-- standard (package) parts on each side, to put a vertical line between them and our areas --}}
    @php
        $hasLeftEnd = $this->hasConfigurableAreaFor('toolbar-left-end');
        $leftStandard = $this->showReorderButton() || $this->showSearchField() || $this->showFiltersButton();
        $rightStandard = $this->showActionsInToolbarRight() || $this->columnSelectIsEnabled
            || ($this->showBulkActionsDropdownAlpine() && $this->shouldAlwaysHideBulkActionsDropdownOption != TRUE);
    @endphp

    <div class="app-table-bar d-flex align-items-center flex-wrap gap-2">
        {{-- Left: around the search --}}
        <div class="d-flex align-items-center flex-wrap gap-2">
            @if ($this->hasConfigurableAreaFor('toolbar-left-start'))
                <div x-cloak x-show="!currentlyReorderingStatus" class="d-flex align-items-center gap-2">
                    @include($this->getConfigurableAreaFor('toolbar-left-start'), $this->getParametersForConfigurableArea('toolbar-left-start'))
                </div>
                @if ($leftStandard)
                    <x-v-divider px=0 class="align-self-stretch app-table-divider" />
                @endif
            @endif

            @if ($this->showReorderButton())
                <x-livewire-tables::tools.toolbar.items.reorder-buttons />
            @endif

            @if ($this->showSearchField())
                <div class="input-group input-group-sm app-table-search" x-cloak x-show="!currentlyReorderingStatus">
                    <span class="input-group-text bg-white no-border-radius"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        wire:model{{ $this->getSearchOptions() }}="search"
                        placeholder="{{ $this->getSearchPlaceholder() }}"
                        {{ (new \Illuminate\View\ComponentAttributeBag($this->getSearchFieldAttributes()))->except(['default', 'default-styling', 'default-colors'])->class(['form-control no-border-radius']) }}
                    >
                    @if ($this->hasSearch())
                        <button type="button" class="btn btn-light gray-border-color no-border-radius" wire:click="clearSearch" title="{{ translator('Reset the search') }}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    @endif
                </div>
            @endif

            @if ($this->showFiltersButton())
                <div class="app-table-filters" x-cloak x-show="!currentlyReorderingStatus">
                    <x-livewire-tables::tools.toolbar.items.filter-button />
                </div>
            @endif

            @if ($hasLeftEnd)
                {{-- a vertical line between the standard parts and ours --}}
                @if ($leftStandard)
                    <x-v-divider px=0 class="align-self-stretch app-table-divider" />
                @endif
                <div x-cloak x-show="!currentlyReorderingStatus" class="d-flex align-items-center gap-2">
                    @include($this->getConfigurableAreaFor('toolbar-left-end'), $this->getParametersForConfigurableArea('toolbar-left-end'))
                </div>
                @if ($this->showActionsInToolbarLeft())
                    <x-v-divider px=0 class="align-self-stretch app-table-divider" />
                @endif
            @endif

            @if ($this->showActionsInToolbarLeft())
                <x-livewire-tables::includes.actions />
            @endif
        </div>

        {{-- Right --}}
        <div class="ms-auto d-flex align-items-center flex-wrap gap-2" x-cloak x-show="!currentlyReorderingStatus">
            @if ($this->hasConfigurableAreaFor('toolbar-right-start'))
                @include($this->getConfigurableAreaFor('toolbar-right-start'), $this->getParametersForConfigurableArea('toolbar-right-start'))
                @if ($rightStandard)
                    <x-v-divider px=0 class="align-self-stretch app-table-divider" />
                @endif
            @endif

            @if ($this->showActionsInToolbarRight())
                <x-livewire-tables::includes.actions />
            @endif

            @if ($this->showBulkActionsDropdownAlpine() && $this->shouldAlwaysHideBulkActionsDropdownOption != TRUE)
                <x-livewire-tables::tools.toolbar.items.bulk-actions />
            @endif

            {{-- Column select: icon only --}}
            @if ($this->columnSelectIsEnabled)
                <div class="position-relative app-table-columns" wire:key="{{ $tableName }}-column-select"
                    x-data="{ open: false }" x-on:keydown.window.escape="open = false" x-on:click.outside="open = false">
                    <button type="button" class="btn btn-light btn-sm gray-border-color shadow no-border-radius" x-on:click="open = !open"
                        title="{{ translator('Columns') }}" aria-haspopup="true" x-bind:aria-expanded="open">
                        <i class="bi bi-layout-three-columns"></i>
                        @if ($this->getSelectableSelectedColumns()->count() < $this->getSelectableColumns()->count())
                            <span class="badge text-bg-primary no-border-radius">{{ $this->getSelectableSelectedColumns()->count() }}/{{ $this->getSelectableColumns()->count() }}</span>
                        @endif
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity class="dropdown-menu show end-0 mt-1 p-2 shadow no-border-radius" style="min-width: 220px; max-height: 60vh; overflow-y: auto">
                        @php($allSelected = $this->getSelectableSelectedColumns()->count() === $this->getSelectableColumns()->count())
                        <label class="form-check d-flex align-items-center gap-2 mb-1" wire:key="{{ $tableName }}-columnSelect-all-{{ (int) $allSelected }}">
                            <input class="form-check-input mt-0" type="checkbox" wire:loading.attr="disabled" @checked($allSelected)
                                wire:click="{{ $allSelected ? 'deselectAllColumns' : 'selectAllColumns' }}">
                            <span class="fw-semibold">{{ translator('All columns') }}</span>
                        </label>
                        <hr class="my-1">
                        @foreach ($this->getColumnsForColumnSelect() as $columnSlug => $columnTitle)
                            <label class="form-check d-flex align-items-center gap-2 mb-1" wire:key="{{ $tableName }}-columnSelect-{{ $loop->index }}">
                                <input class="form-check-input mt-0" type="checkbox" value="{{ $columnSlug }}"
                                    wire:model.live="selectedColumns" wire:target="selectedColumns" wire:loading.attr="disabled">
                                <span>{{ $columnTitle }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->hasConfigurableAreaFor('toolbar-right-end'))
                @if ($rightStandard)
                    <x-v-divider px=0 class="align-self-stretch app-table-divider" />
                @endif
                @include($this->getConfigurableAreaFor('toolbar-right-end'), $this->getParametersForConfigurableArea('toolbar-right-end'))
            @endif
        </div>
    </div>
    <hr class="app-table-bar-line mt-2 mb-2">
@endif
