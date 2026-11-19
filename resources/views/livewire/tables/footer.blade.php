{{--
    Our footer of an AppDataTable (replaces the package pagination block, rendered in the package area before-pagination).
    Left: rows per page and "x–y / total"; right: the pagination (livewire.partials.pagination, like our own tables).
    $currentRows comes from the package pagination component this view is included in.
--}}
@aware(['tableName'])

@if ($ownArea)
    @include($ownArea, $ownAreaParams)
@endif

@if ($this->showAppFooter())
    @php($isPaginator = $currentRows instanceof \Illuminate\Contracts\Pagination\Paginator || $currentRows instanceof \Illuminate\Contracts\Pagination\CursorPaginator)
    <hr class="app-table-footer-line mt-2 mb-0">
    <div class="app-table-footer d-flex align-items-center flex-wrap gap-2 pt-2 pb-2">
        {{-- Left: rows per page, results --}}
        <div class="d-flex align-items-center gap-2">
            @if ($this->showPaginationDropdown())
                <div class="input-group input-group-sm w-auto" title="{{ translator('Rows per page') }}">
                    <span class="input-group-text bg-white no-border-radius"><i class="bi bi-list-ol"></i></span>
                    <select wire:model.live="perPage" id="{{ $tableName }}-perPage"
                        {{ (new \Illuminate\View\ComponentAttributeBag($this->getPerPageFieldAttributes()))->except(['default', 'default-styling', 'default-colors'])->class(['form-select no-border-radius']) }}>
                        @foreach ($this->getPerPageAccepted() as $item)
                            <option value="{{ $item }}" wire:key="{{ $tableName }}-per-page-{{ $item }}">{{ $item === -1 ? translator('All') : $item }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($this->showPaginationDetails)
                <span class="small text-muted text-nowrap">
                    @if ($currentRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        @if ($currentRows->total())
                            {{ $currentRows->firstItem() }}–{{ $currentRows->lastItem() }} / {{ $currentRows->total() }}
                        @else
                            0
                        @endif
                    @elseif ($isPaginator)
                        @if ($currentRows->count()) {{ $currentRows->firstItem() ?? '' }}{{ method_exists($currentRows, 'lastItem') && $currentRows->lastItem() ? '–' . $currentRows->lastItem() : '' }} @endif
                    @else
                        {{ $currentRows->count() }}
                    @endif
                </span>
            @endif
        </div>

        {{-- Right: pagination --}}
        @if ($this->paginationIsEnabled && $isPaginator)
            <div class="ms-auto">
                @if ($this->isPaginationMethod('standard'))
                    {{ $currentRows->onEachSide(1)->links('livewire.partials.pagination') }}
                @else
                    {{-- simple / cursor: the package view (no page numbers) --}}
                    {{ $currentRows->links('livewire-tables::specific.bootstrap-4.simple-pagination') }}
                @endif
            </div>
        @endif
    </div>
@endif
