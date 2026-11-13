@php
    $buttons = [
        'critical' => ['color' => 'danger',  'icon' => 'x-octagon-fill',            'label' => 'Critical', 'hint' => 'QueryException (missing table)'],
        'error'    => ['color' => 'warning', 'icon' => 'exclamation-triangle-fill', 'label' => 'Error',    'hint' => 'Error (method call on null)'],
        'warning'  => ['color' => 'info',    'icon' => 'exclamation-circle-fill',   'label' => 'Warning',  'hint' => 'AppWarningException'],
    ];
    $confirm = $isProduction ? translator('This is PRODUCTION. Trigger a test error anyway?') : NULL;
@endphp
<div class="d-flex flex-column h-100">
    {{-- Options --}}
    <div class="px-3 py-2 border-bottom d-flex flex-wrap align-items-center gap-3 small">
        <div class="btn-group btn-group-sm" role="group" aria-label="{{ translator('Mode') }}">
            <button type="button" class="btn {{ $mode === 'report' ? 'btn-secondary' : 'btn-light border' }}" style="border-radius: 0"
                wire:click="$set('mode', 'report')" title="{{ translator('Caught and passed to report(): goes through the exception handler, the page keeps working') }}">
                <i class="bi bi-send"></i> {{ translator('Report') }}
            </button>
            <button type="button" class="btn {{ $mode === 'throw' ? 'btn-secondary' : 'btn-light border' }}" style="border-radius: 0"
                wire:click="$set('mode', 'throw')" title="{{ translator('Real uncaught exception in this Livewire request (you will see the error page / modal)') }}">
                <i class="bi bi-lightning"></i> {{ translator('Throw') }}
            </button>
        </div>

        <div class="d-flex align-items-center gap-1" @if($mode === 'throw') title="{{ translator('Only in report mode') }}" @endif>
            <span class="text-muted">{{ translator('Repeat') }}</span>
            <select class="form-select form-select-sm" style="width: auto" wire:model.live="repeat" @disabled($mode === 'throw')>
                @foreach (\App\Livewire\Modules\AppSettings\Dashboard\DevTool::REPEATS as $r)
                    <option value="{{ $r }}">&times;{{ $r }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-check form-switch m-0" title="{{ translator('Off: the same error is grouped (counter goes up). On: every click creates a new error group.') }}">
            <input class="form-check-input" type="checkbox" id="devtool-unique-{{ $tileId }}" wire:model.live="unique">
            <label class="form-check-label" for="devtool-unique-{{ $tileId }}">{{ translator('New group each time') }}</label>
        </div>
    </div>

    {{-- Trigger buttons --}}
    <div class="px-3 py-3 d-flex flex-wrap gap-2 border-bottom">
        @foreach ($buttons as $level => $b)
            <button type="button" class="btn btn-sm btn-{{ $b['color'] }} shadow d-flex align-items-center gap-2" style="border-radius: 0"
                wire:click="trigger('{{ $level }}')" wire:loading.attr="disabled" wire:target="trigger"
                title="{{ $b['hint'] }}" @if($confirm) wire:confirm="{{ $confirm }}" @endif>
                <i class="bi bi-{{ $b['icon'] }}"></i> {{ translator($b['label']) }}
            </button>
        @endforeach
        <x-v-divider px=0 />
        <x-ui.btn type="lig.sm" icon="bell" action="sendTestNotification" text="{{ translator('Test notification to me') }}" />
    </div>

    {{-- Log --}}
    <div class="flex-fill" style="overflow-y: auto">
        <div class="d-flex justify-content-between align-items-center px-3 pt-2 pb-1">
            <span class="text-muted text-uppercase fw-semibold" style="font-size: .7rem">{{ translator('Triggered') }}</span>
            @if ($log)
                <button type="button" class="btn btn-link btn-sm p-0 small text-muted" wire:click="clearLog">{{ translator('Clear') }}</button>
            @endif
        </div>
        @forelse ($log as $entry)
            @php $b = $buttons[$entry['level']] ?? ['color' => 'secondary', 'icon' => 'bell', 'label' => ucfirst($entry['level'])]; @endphp
            <div class="d-flex align-items-center gap-2 px-3 py-1 small border-top">
                <span class="text-muted font-monospace">{{ $entry['time'] }}</span>
                <i class="bi bi-{{ $b['icon'] }} text-{{ $b['color'] }}"></i>
                <span>{{ translator($b['label']) }}</span>
                <span class="text-muted">· {{ $entry['mode'] }}@if($entry['count'] > 1) &times;{{ $entry['count'] }}@endif @if($entry['unique']) · {{ translator('new group') }}@endif</span>
            </div>
        @empty
            <div class="px-3 py-2 small text-muted">{{ translator('Nothing triggered yet. Errors show up in the App errors widget.') }}</div>
        @endforelse
    </div>
</div>
