{{--
    Compact Livewire pagination (page buttons only, square corners).
    Usage in a component with WithPagination: {{ $rows->onEachSide(1)->links('livewire.partials.pagination') }}
    The "x–y / total" text is up to the view, so it can be translated.
--}}
<div>
    @if ($paginator->hasPages())
        @php($pageName = $paginator->getPageName())
        <div role="navigation">
            <ul class="pagination pagination-sm mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link rounded-0">&lsaquo;</span></li>
                @else
                    <li class="page-item"><button type="button" class="page-link rounded-0" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled">&lsaquo;</button></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link rounded-0">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" wire:key="paginator-{{ $pageName }}-page-{{ $page }}"><span class="page-link rounded-0">{{ $page }}</span></li>
                            @else
                                <li class="page-item" wire:key="paginator-{{ $pageName }}-page-{{ $page }}"><button type="button" class="page-link rounded-0" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')">{{ $page }}</button></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li class="page-item"><button type="button" class="page-link rounded-0" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled">&rsaquo;</button></li>
                @else
                    <li class="page-item disabled"><span class="page-link rounded-0">&rsaquo;</span></li>
                @endif
            </ul>
        </div>
    @endif
</div>
