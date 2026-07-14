<style>
    [x-cloak] { display: none !important; }
</style>

<div
    wire:key="global-modal-root-{{ $renderVersion }}"
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
    <div
        x-show.important="isOpen"
        x-transition.opacity
        class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-start justify-content-center pt-4 pb-4 px-2"
        style="z-index: 1600;"
        @click.self="close()"
    >
        <div
            class="bg-white shadow w-100 position-relative no-border-radius"
            style="max-width: {{ $maxWidth }};"
        >
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <strong style="{{ $headerStyle }}">{{ $headerName }}</strong>
                <button type="button" class="btn-close" aria-label="Close" @click="close()"></button>
            </div>

            <div class="px-5 py-3" wire:key="global-modal-content-{{ $renderVersion }}">
                @if ($componentName)
                    @livewire($componentName, $componentParams, key('global-modal-' . $componentName . '-' . md5(json_encode($componentParams)) . '-' . $renderVersion))
                @endif
                {{ var_export([$componentName, $renderVersion, $time]) }}
            </div>
        </div>
    </div>
</div>

