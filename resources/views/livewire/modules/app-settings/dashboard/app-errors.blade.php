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
                            <x-ui.btn type="lig.sm" icon="arrows-fullscreen" action="openDetails" param="{{ $error->id }}" text="{{ translator('All details') }}" />
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

    {{-- Details modal (all information of one error) --}}
    @if ($detail)
        @php
            $d = $detail->data ?? [];
            $ctx = $d['context'] ?? [];
            $s = $levelStyle[$detail->level] ?? $levelStyle['error'];
            $trace = $d['trace'] ?? [];
            $raw = json_encode($detail->only(['id', 'type', 'level', 'title', 'message', 'fingerprint', 'occurrences', 'first_seen_at', 'last_seen_at', 'user_id', 'company_id', 'resolved_at', 'resolved_by']) + ['data' => $d],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $row = fn ($label, $value) => $value === NULL || $value === '' ? '' : '<tr><td class="text-muted ps-0 text-nowrap" style="width: 150px">' . e(translator($label)) . '</td><td class="font-monospace text-break">' . e($value) . '</td></tr>';
        @endphp
        <x-ui.modal title="{{ translator('Error details') }} #{{ $detail->id }}" :modalStatus=$modalStatus size="xl" wKey="err-detail-{{ $tileId }}">
            <x-slot:subtitle>
                <div class="d-flex align-items-center gap-2 small mt-1">
                    <span class="badge text-bg-{{ $s['color'] }}"><i class="bi bi-{{ $s['icon'] }}"></i> {{ translator(ucfirst($detail->level)) }}</span>
                    @if ($detail->isResolved())
                        <span class="badge text-bg-secondary"><i class="bi bi-check-circle"></i> {{ translator('Resolved') }}</span>
                    @else
                        <span class="badge text-bg-light border">{{ translator('Open') }}</span>
                    @endif
                    <span class="text-muted">&times;{{ number_format($detail->occurrences, 0, ',', '.') }} · {{ translator('last seen') }} {{ $detail->last_seen_at?->diffForHumans() }}</span>
                </div>
            </x-slot:subtitle>

            {{-- Message --}}
            <div class="mb-3">
                <div class="fw-semibold mb-1">{{ $detail->title }}</div>
                <div class="border bg-body-tertiary p-2 small text-break" style="white-space: pre-wrap; max-height: 200px; overflow: auto">{{ $detail->message }}</div>
            </div>

            <div class="row g-3">
                {{-- Overview --}}
                <div class="col-lg-6">
                    <div class="text-muted text-uppercase fw-semibold mb-1" style="font-size: .7rem">{{ translator('Overview') }}</div>
                    <table class="table table-sm table-borderless small mb-0">
                        {!! $row('Exception', $d['exception'] ?? NULL) !!}
                        {!! $row('Code', isset($d['code']) && $d['code'] !== 0 ? (string) $d['code'] : NULL) !!}
                        {!! $row('File', isset($d['file']) ? $d['file'] . ':' . ($d['line'] ?? '') : NULL) !!}
                        {!! $row('Caused by', $d['previous'] ?? NULL) !!}
                        {!! $row('Occurrences', number_format($detail->occurrences, 0, ',', '.')) !!}
                        {!! $row('First seen', $detail->first_seen_at?->format('d.m.Y H:i:s')) !!}
                        {!! $row('Last seen', $detail->last_seen_at?->format('d.m.Y H:i:s')) !!}
                        {!! $row('Environment', $d['env'] ?? NULL) !!}
                        {!! $row('Resolved', $detail->isResolved() ? $detail->resolved_at->format('d.m.Y H:i:s') . ' · ' . ($detail->resolver?->name ?? '') : NULL) !!}
                        {!! $row('Fingerprint', $detail->fingerprint) !!}
                    </table>
                </div>

                {{-- Where / who --}}
                <div class="col-lg-6">
                    <div class="text-muted text-uppercase fw-semibold mb-1" style="font-size: .7rem">{{ translator('Where & who') }}</div>
                    <table class="table table-sm table-borderless small mb-0">
                        {!! $row('Source', ($ctx['kind'] ?? NULL) === 'console' ? 'Console' : (($ctx['kind'] ?? NULL) === 'http' ? 'HTTP' : NULL)) !!}
                        {!! $row('Command', $ctx['command'] ?? NULL) !!}
                        {!! $row('Request', isset($ctx['method']) ? $ctx['method'] . ' ' . ($ctx['path'] ?? '') : NULL) !!}
                        {!! $row('Route', $ctx['route'] ?? NULL) !!}
                        {!! $row('Livewire component', $ctx['livewire']['component'] ?? NULL) !!}
                        {!! $row('Livewire method', isset($ctx['livewire']['method']) ? $ctx['livewire']['method'] . '()' : NULL) !!}
                        {!! $row('Page', isset($ctx['livewire']['page']) ? '/' . ltrim($ctx['livewire']['page'], '/') : NULL) !!}
                        {!! $row('User', ($detail->user?->name ?? ($d['user']['name'] ?? NULL)) ? ($detail->user?->name ?? $d['user']['name']) . ' (#' . ($detail->user_id ?? $d['user']['id'] ?? '?') . ')' : translator('Guest / system')) !!}
                    </table>
                </div>
            </div>

            {{-- Stack trace --}}
            @if ($trace)
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: .7rem">{{ translator('Stack trace') }} ({{ count($trace) }})</span>
                        <span class="small text-muted"><span class="badge text-bg-dark">app</span> <span class="badge text-bg-secondary opacity-50">vendor</span></span>
                    </div>
                    <div class="bg-dark text-light small font-monospace" style="max-height: 320px; overflow: auto">
                        @foreach ($trace as $i => $f)
                            @php $isVendor = str_starts_with($f['file'] ?? '', 'vendor/') || ($f['file'] ?? NULL) === NULL; @endphp
                            <div class="d-flex gap-2 px-2 py-1 border-bottom border-secondary {{ $isVendor ? 'opacity-50' : '' }}">
                                <span class="text-secondary text-end" style="min-width: 2rem">#{{ $i }}</span>
                                <div class="text-break">
                                    <div class="{{ $isVendor ? '' : 'text-warning' }}">{{ $f['function'] }}()</div>
                                    <div class="text-secondary">{{ $f['file'] ?? '[internal]' }}@if($f['line'] ?? NULL):{{ $f['line'] }}@endif</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Raw JSON --}}
            <details class="mt-3">
                <summary class="small text-muted" style="cursor: pointer">{{ translator('Raw data (JSON)') }}</summary>
                <div class="position-relative mt-1" x-data="{ copied: false }">
                    <button type="button" class="btn btn-sm btn-light border position-absolute top-0 end-0 m-1" style="border-radius: 0"
                        x-on:click="navigator.clipboard.writeText($refs.raw.textContent).then(() => { copied = true; setTimeout(() => copied = false, 1500) })">
                        <i class="bi" x-bind:class="copied ? 'bi-check2' : 'bi-clipboard'"></i>
                        <span x-text="copied ? '{{ translator('Copied') }}' : '{{ translator('Copy') }}'"></span>
                    </button>
                    <pre class="bg-body-tertiary border p-2 small mb-0" style="max-height: 320px; overflow: auto" x-ref="raw">{{ $raw }}</pre>
                </div>
            </details>

            <x-slot:footerLeft>
                <span class="small text-muted">{{ translator('Type') }}: {{ $detail->typeInfo()['label'] }} · ID {{ $detail->id }}</span>
            </x-slot:footerLeft>
            <x-slot:footerRight>
                @if ($detail->isResolved())
                    <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" action="reopen" param="{{ $detail->id }}" text="{{ translator('Reopen') }}" />
                @else
                    <x-ui.btn type="suc.sm" icon="check2" action="resolve" param="{{ $detail->id }}" text="{{ translator('Mark resolved') }}" />
                @endif
                <x-ui.btn type="sec.sm" action="closeModal" text="{{ translator('Close') }}" />
            </x-slot:footerRight>
        </x-ui.modal>
    @endif
</div>
