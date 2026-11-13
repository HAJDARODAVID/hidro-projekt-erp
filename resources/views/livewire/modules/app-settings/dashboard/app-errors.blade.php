@php
    $levelStyle = [
        'critical' => ['color' => 'danger',  'icon' => 'x-octagon-fill'],
        'error'    => ['color' => 'warning', 'icon' => 'exclamation-triangle-fill'],
        'warning'  => ['color' => 'info',    'icon' => 'exclamation-circle-fill'],
    ];
@endphp
<div class="d-flex flex-column h-100" wire:poll.60s>
    {{-- Summary per level (occurrences of open errors, click = quick filter) --}}
    <div class="d-flex gap-2 p-2 border-bottom flex-wrap align-items-center">
        @foreach ($counts->reverse() as $level => $count)
            @php $s = $levelStyle[$level]; $active = $levelFilter === $level; @endphp
            <button type="button" wire:click="setLevelFilter('{{ $level }}')"
                class="btn btn-sm d-flex align-items-center gap-2 {{ $active ? 'btn-' . $s['color'] : 'btn-light border' }}"
                style="border-radius: 0" title="{{ translator('Occurrences of open errors') }}">
                <i class="bi bi-{{ $s['icon'] }} {{ $active ? '' : 'text-' . $s['color'] }}"></i>
                <span>{{ translator(ucfirst($level)) }}</span>
                <span class="badge {{ $active ? 'text-bg-light' : 'text-bg-' . $s['color'] }}">{{ number_format($count, 0, ',', '.') }}</span>
            </button>
        @endforeach

        <div class="ms-auto d-flex align-items-center gap-2 small text-muted">
            <span>{{ $total }} {{ translator('unique') }}</span>
            @if ($unread)
                <button type="button" class="btn btn-sm btn-link p-0 small" wire:click="markAllRead" title="{{ translator('Mark all as read') }}">
                    <span class="badge text-bg-primary">{{ $unread }} {{ translator('new') }}</span>
                </button>
            @endif
            <div class="form-check form-switch m-0" title="{{ translator('Show resolved errors') }}">
                <input class="form-check-input" type="checkbox" id="resolved-{{ $tileId }}" wire:click="toggleShowResolved" @checked($showResolved)>
                <label class="form-check-label" for="resolved-{{ $tileId }}">{{ translator('Resolved') }} ({{ $resolvedCount }})</label>
            </div>
        </div>
    </div>

    {{-- Error list --}}
    <div class="flex-fill" style="overflow-y: auto" wire:loading.class="opacity-50">
        @forelse ($items as $error)
            @php
                $s = $levelStyle[$error->level] ?? $levelStyle['error'];
                $isOpen = $expanded === $error->id;
                $isUnread = !$error->isResolved() && $error->reads->isEmpty();
                $d = $error->data ?? [];
                $ctx = $d['context'] ?? [];
                $where = match ($ctx['kind'] ?? NULL) {
                    'console' => 'artisan ' . preg_replace('/^artisan\s*/', '', $ctx['command'] ?? ''),
                    'http'    => isset($ctx['livewire']['component'])
                                    ? $ctx['livewire']['component'] . ($ctx['livewire']['method'] ? '::' . $ctx['livewire']['method'] . '()' : '')
                                    : ($ctx['method'] ?? '') . ' ' . ($ctx['path'] ?? ''),
                    default   => NULL,
                };
                $userName = $error->user?->name ?? ($d['user']['name'] ?? NULL);
            @endphp
            <div class="border-bottom border-start border-4 border-{{ $error->isResolved() ? 'secondary' : $s['color'] }} @if($error->isResolved()) opacity-75 @endif"
                wire:key="err-{{ $tileId }}-{{ $error->id }}">
                <div class="d-flex align-items-start gap-2 px-2 py-2" style="cursor: pointer" wire:click="toggleExpanded({{ $error->id }})">
                    <i class="bi bi-{{ $error->isResolved() ? 'check-circle-fill text-secondary' : $s['icon'] . ' text-' . $s['color'] }} mt-1"></i>
                    <div class="flex-fill" style="min-width: 0">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small fw-semibold text-truncate" title="{{ $d['exception'] ?? $error->title }}">
                                @if ($isUnread) <span class="d-inline-block rounded-circle bg-primary me-1" style="width: 7px; height: 7px" title="{{ translator('New') }}"></span> @endif
                                {{ $error->title }}
                            </span>
                            <span class="small text-muted text-nowrap" title="{{ $error->last_seen_at?->format('d.m.Y H:i:s') }}">{{ $error->last_seen_at?->diffForHumans() }}</span>
                        </div>
                        <div class="small text-truncate" title="{{ $error->message }}">{{ $error->message }}</div>
                        <div class="small text-muted text-truncate font-monospace" style="font-size: .75rem">
                            @if (isset($d['file'])) {{ $d['file'] }}:{{ $d['line'] ?? '' }} @endif
                        </div>
                    </div>
                    <span class="badge rounded-pill text-bg-secondary align-self-center" title="{{ translator('Occurrences') }}">&times;{{ number_format($error->occurrences, 0, ',', '.') }}</span>
                </div>

                @if ($isOpen)
                    <div class="px-3 pb-2 small">
                        <div class="text-break mb-2" style="white-space: pre-wrap">{{ $error->message }}</div>
                        <table class="table table-sm table-borderless mb-2 small">
                            <tr><td class="text-muted ps-0" style="width: 110px">{{ translator('Exception') }}</td><td class="font-monospace text-break">{{ $d['exception'] ?? '—' }}</td></tr>
                            @if ($where)
                                <tr><td class="text-muted ps-0">{{ translator('Where') }}</td><td class="font-monospace text-break">{{ $where }}</td></tr>
                            @endif
                            @if (isset($ctx['livewire']['page']))
                                <tr><td class="text-muted ps-0">{{ translator('Page') }}</td><td class="font-monospace text-break">/{{ ltrim($ctx['livewire']['page'], '/') }}</td></tr>
                            @endif
                            <tr><td class="text-muted ps-0">{{ translator('User') }}</td><td>{{ $userName ?? translator('Guest / system') }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('Occurrences') }}</td><td>{{ number_format($error->occurrences, 0, ',', '.') }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('First seen') }}</td><td>{{ $error->first_seen_at?->format('d.m.Y H:i:s') }}</td></tr>
                            <tr><td class="text-muted ps-0">{{ translator('Last seen') }}</td><td>{{ $error->last_seen_at?->format('d.m.Y H:i:s') }}</td></tr>
                            @if (!empty($d['previous']))
                                <tr><td class="text-muted ps-0">{{ translator('Caused by') }}</td><td class="font-monospace text-break">{{ $d['previous'] }}</td></tr>
                            @endif
                            @if ($error->isResolved())
                                <tr><td class="text-muted ps-0">{{ translator('Resolved') }}</td><td>{{ $error->resolved_at->format('d.m.Y H:i') }} · {{ $error->resolver?->name }}</td></tr>
                            @endif
                        </table>

                        @if (!empty($d['trace']))
                            <pre class="bg-dark text-light p-2 mb-2 small" style="max-height: 180px; overflow: auto; white-space: pre-wrap;">@foreach ($d['trace'] as $i => $f)<span class="{{ str_starts_with($f['file'] ?? '', 'vendor/') ? 'opacity-50' : '' }}">#{{ $i }} {{ $f['file'] ?? '[internal]' }}@if($f['line'])({{ $f['line'] }})@endif: {{ $f['function'] }}()</span>
@endforeach</pre>
                        @endif

                        <div class="d-flex gap-2">
                            @if ($error->isResolved())
                                <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" action="reopen" param="{{ $error->id }}" text="{{ translator('Reopen') }}" />
                            @else
                                <x-ui.btn type="suc.sm" icon="check2" action="resolve" param="{{ $error->id }}" text="{{ translator('Mark resolved') }}" />
                            @endif
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
