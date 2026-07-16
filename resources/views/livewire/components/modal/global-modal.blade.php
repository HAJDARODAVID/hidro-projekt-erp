<div
    x-cloak
    x-data="{
        isOpen: false,
        close() {
            this.isOpen = false;
            $wire.clearComponent();
        }
    }"
    @global-modal-open-overlay.window="isOpen = true"
    @keydown.window.escape="if (isOpen) close()"
>
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div
        x-show.important="isOpen"
        x-transition.opacity
        class="position-fixed top-0 start-0 w-100 h-100 modal-bg-blur d-flex align-items-start justify-content-center pt-4 pb-4 px-2"
        style="z-index: 1600;"
        @click.self="close()"
    >
        <div
            class="bg-white shadow w-100 position-relative no-border-radius"
            style="max-width: {{ $modalService->getMaxWidth() }};"
        >
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <strong style="{{ $modalService->getHeaderStyle() }}">{{ $modalService->getHeaderName() }}</strong>
                <button type="button" class="btn-close" aria-label="Close" @click="close()"></button>
            </div>

            <div class="px-5 py-3" wire:key="global-modal-content-{{ $renderVersion }}">
                @if ($modalService->getComponentPath())
                    @livewire($modalService->getComponentPath(), $componentParams, key('global-modal-' . $modalService->getComponentPath() . '-' . md5(json_encode($componentParams)) . '-' . $renderVersion))
                @endif
            </div>
        </div>
    </div>
</div>

