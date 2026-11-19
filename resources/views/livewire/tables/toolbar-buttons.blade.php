{{-- Our buttons in a toolbar area of an AppDataTable (see AppDataTable::toolbarButtons()) --}}
<div class="d-flex align-items-center gap-2 app-table-toolbar">
    @foreach ($buttons as $i => $button)
        @php($class = 'btn btn-sm shadow no-border-radius ' . (\App\View\Components\Ui\Btn::BTN_COLORS[$button['type']] ?? \App\View\Components\Ui\Btn::BTN_COLORS['lig']))
        @php($content = ($button['icon'] ? '<i class="bi bi-' . e($button['icon']) . '"></i>' : '') . ($button['text'] ? '<span>' . e($button['text']) . '</span>' : ''))

        @if (!empty($button['route']))
            <a href="{{ route($button['route'], $button['params'] ?? []) }}" class="{{ $class }} d-inline-flex align-items-center gap-2" @if ($button['title']) title="{{ $button['title'] }}" @endif>{!! $content !!}</a>
        @else
            <button type="button" class="{{ $class }} d-inline-flex align-items-center gap-2" wire:key="app-table-btn-{{ $i }}"
                @if ($button['title']) title="{{ $button['title'] }}" @endif
                @if ($button['confirm']) wire:confirm="{{ $button['confirm'] }}" @endif
                @if (!empty($button['modal']))
                    wire:click="openToolbarModal(@js($button['modal']), @js($button['params'] ?? []))"
                @elseif (!empty($button['action']))
                    wire:click="{{ $button['action'] }}(@js($button['param'] ?? NULL))"
                    wire:loading.attr="disabled" wire:target="{{ $button['action'] }}"
                @endif
            >{!! $content !!}</button>
        @endif
    @endforeach
</div>
