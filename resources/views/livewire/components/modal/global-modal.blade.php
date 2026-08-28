<div
    x-cloak
    x-data="{
        isOpen: false,
        beforeCloseMethod: null,
        async close() {
            if (this.beforeCloseMethod) {
                const el = this.$refs.modalBody?.querySelector('[wire\\:id]');
                const childWire = el ? window.Livewire.find(el.getAttribute('wire:id')) : null;

                if (childWire && typeof childWire[this.beforeCloseMethod] === 'function') {
                    try {
                        await childWire[this.beforeCloseMethod]();
                    } catch (error) {
                        console.error('[global-modal] before-close method failed:', error);
                    }
                }
            }

            this.isOpen = false;
            $wire.clearComponent();
        }
    }"
    @global-modal-open-overlay.window="isOpen = true; beforeCloseMethod = $event.detail.beforeClose ?? null"
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

            <div
                class="px-4 py-3"
                x-ref="modalBody"
                wire:key="global-modal-content-{{ $modalService->isStable() ? $modalService->getComponentPath() : $renderVersion }}"
            >
                @if ($modalService->getComponentPath())
                    @livewire(
                        $modalService->getComponentPath(),
                        ['params' => $componentParams],
                        key(
                            $modalService->isStable()
                                ? 'global-modal-' . $modalService->getComponentPath()
                                : 'global-modal-' . $modalService->getComponentPath() . '-' . md5(json_encode($componentParams)) . '-' . $renderVersion
                        )
                    )
                @endif
            </div>
        </div>
    </div>
</div>

