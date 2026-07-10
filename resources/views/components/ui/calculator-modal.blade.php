<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div
    x-cloak
    x-data="calculatorModal()"
    @keydown.window="if (isOpen && $event.key === 'Escape') { close() }"
>
    <div
        x-show.important="isOpen"
        x-transition.opacity
        class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-start justify-content-center pt-4 pb-4 px-2"
        style="z-index: 1600;"
        @click.self="close()"
    >
        <div class="bg-white shadow w-100 position-relative no-border-radius" style="max-width: 1140px;">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <strong>Kalkulator</strong>
                <button type="button" class="btn-close" aria-label="Close" @click="close()"></button>
            </div>
            <div class="px-5 py-3">
                @livewire('components.helpers.calculator', [], key('calculator-modal-helper'))
            </div>
        </div>
    </div>
</div>
