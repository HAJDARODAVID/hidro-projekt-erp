@php
    $shortcuts = app(\App\Services\Application\QuickAccessShortcutsService::class)->getShortcutsForClient();
@endphp

@if (!empty($shortcuts))
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div
        x-cloak
        x-data="quickAccessModal({
            shortcuts: {{ Illuminate\Support\Js::from($shortcuts) }},
            actionEndpoint: '{{ route('quickAccess.execute') }}'
        })"
        @keydown.window="handleGlobalKeydown($event)"
    >
        <div
            x-show.important="isOpen"
            x-transition.opacity
            class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-start justify-content-center pt-5"
            style="z-index: 1500;"
            @click.self="close()"
        >
            <div class="bg-white rounded shadow w-100 position-relative" style="max-width: 560px;">
                <div :style="isExecuting ? 'filter: blur(2px); pointer-events: none;' : ''">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <strong>Quick access</strong>
                        <small class="text-muted">Ctrl + Q</small>
                    </div>
                    <div class="p-3">
                        <input
                            x-ref="shortcutInput"
                            x-model="query"
                            @input="handleInput()"
                            @keydown.enter.prevent="executeCurrentInput()"
                            type="text"
                            class="form-control no-border-radius"
                            placeholder="Type code (ATT, CLR...)"
                        />
                        <div x-show="errorMessage" class="text-danger small mt-2" x-text="errorMessage"></div>
                        <div x-show="feedbackMessage" class="text-success small mt-2" x-text="feedbackMessage"></div>

                        <div class="mt-3" x-show="query.trim().length > 0">
                            <template x-for="shortcut in filteredShortcuts" :key="shortcut.code">
                                <button
                                    type="button"
                                    class="btn btn-light border d-flex w-100 justify-content-between align-items-center mb-2"
                                    @click="executeShortcut(shortcut)"
                                >
                                    <span class="text-start" x-text="shortcut.label"></span>
                                    <span class="badge text-bg-dark" x-text="shortcut.code"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div
                    x-cloak
                    x-show.important="isExecuting"
                    x-transition.opacity
                    class="position-absolute top-0 start-0 w-100 h-100"
                    style="background: rgba(255, 255, 255, 0.45);"
                >
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                        <div class="spinner-border text-dark" role="status" aria-label="Loading"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
