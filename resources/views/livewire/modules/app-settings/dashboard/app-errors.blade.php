@php
    $levelStyle = [
        'critical' => ['color' => 'danger',  'icon' => 'x-octagon-fill'],
        'error'    => ['color' => 'warning', 'icon' => 'exclamation-triangle-fill'],
        'warning'  => ['color' => 'info',    'icon' => 'exclamation-circle-fill'],
    ];
@endphp
<div class="d-flex flex-column h-100">
    {{-- Summary per level (click = quick filter) --}}
    <div class="d-flex gap-2 p-2 border-bottom flex-wrap">
        @foreach ($counts->reverse() as $level => $count)
            @php $s = $levelStyle[$level]; $active = $levelFilter === $level; @endphp
            <button type="button" wire:click="setLevelFilter('{{ $level }}')"
                class="btn btn-sm d-flex align-items-center gap-2 {{ $active ? 'btn-' . $s['color'] : 'btn-light border' }}"
                style="border-radius: 0">
                <i class="bi bi-{{ $s['icon'] }} {{ $active ? '' : 'text-' . $s['color'] }}"></i>
                <span class="text-capitalize">{{ translator(ucfirst($level)) }}</span>
                <span class="badge {{ $active ? 'text-bg-light' : 'text-bg-' . $s['color'] }}">{{ $count }}</span>
            </button>
        @endforeach
        <div class="ms-auto small text-muted align-self-center">
            {{ $total }} {{ translator('unique') }} &middot; {{ translator('dummy data') }}
        </div>
    </div>

    {{-- Error list --}}
    <div class="flex-fill" style="overflow-y: auto" wire:loading.class="opacity-50">
        @forelse ($errors as $error)
            @php $s = $levelStyle[$error['level']]; $isOpen = $expanded === $error['id']; @endphp
            <div class="border-bottom border-start border-4 border-{{ $s['color'] }}" wire:key="err-{{ $tileId }}-{{ $error['id'] }}">
                <div class="d-flex align-items-start gap-2 px-2 py-2" style="cursor: pointer" wire:click="toggleExpanded({{ $error['id'] }})">
                    <i class="bi bi-{{ $s['icon'] }} text-{{ $s['color'] }} mt-1"></i>
                    <div class="flex-fill" style="min-width: 0">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small fw-semibold text-truncate" title="{{ $error['exception'] }}">{{ class_basename($error['exception']) }}</span>
                            <span class="small text-muted text-nowrap" title="{{ $error['last_seen']->format('d.m.Y H:i:s') }}">{{ $error['last_seen']->diffForHumans() }}</span>
                        </div>
                        <div class="small text-truncate" title="{{ $error['message'] }}">{{ $error['message'] }}</div>
                        <div class="small text-muted text-truncate font-monospace" style="font-size: .75rem">{{ $error['file'] }}:{{ $error['line'] }}</div>
                    </div>
                    <span class="badge rounded-pill text-bg-secondary align-self-center" title="{{ translator('Occurrences') }}">&times;{{ $error['count'] }}</span>
                </div>

                @if ($isOpen)
                    <div class="px-3 pb-2 small">
                        <table class="table table-sm table-borderless mb-2 small">
                            <tr><td class="text-muted ps-0" style="width: 110px">{{ translator('Exception') }}</td><td class="font-monospace">{{ $error['exception'] }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('Request') }}</td><td class="font-monospace">{{ $error['url'] }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('User') }}</td><td>{{ $error['user'] }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('Last seen') }}</td><td>{{ $error['last_seen']->format('d.m.Y H:i:s') }}</td></tr>
                        </table>
                        <pre class="bg-dark text-light p-2 mb-2 small" style="max-height: 140px; overflow: auto; white-space: pre-wrap;">#0 {{ $error['file'] }}({{ $error['line'] }})
#1 vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php(456)
#2 vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36)
#3 vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(183)
#4 public/index.php(52)</pre>
                        <div class="d-flex gap-2">
                            <x-ui.btn type="suc.sm" icon="check2" action="resolve" param="{{ $error['id'] }}" text="{{ translator('Mark resolved') }}" />
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted py-4">
                <i class="bi bi-check-circle fs-2 text-success"></i>
                <div class="small mt-1">{{ translator('No errors for the selected period.') }}</div>
            </div>
        @endforelse
    </div>
</div>
