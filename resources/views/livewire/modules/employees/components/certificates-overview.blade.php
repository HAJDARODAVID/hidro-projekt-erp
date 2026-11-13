{{-- Certificates & checks of all workers. Variant "full" = global modal, "card" = dashboard card --}}
<div>
    @php
        $badge = 'livewire.modules.employees.components.partials.certificate-state-badge';
        $stateLabels = ['expired' => translator('Expired'), 'missing' => translator('Missing'), 'due-soon' => translator('Due soon')];
        $daysText = function ($row) {
            if ($row['daysLeft'] === NULL) return '';
            if ($row['daysLeft'] < 0) return translator('overdue') . ' ' . abs($row['daysLeft']) . ' ' . translator('days');
            if ($row['daysLeft'] === 0) return translator('today');
            return translator('in') . ' ' . $row['daysLeft'] . ' ' . translator('days');
        };
        /**Worker info opened on the worker's Certificates & checks tab */
        $workerUrl = fn ($row) => \Illuminate\Support\Facades\Route::has('getWorkerInfo')
            ? route('getWorkerInfo', ['worker' => $row['workerId'], 'tab' => 'certificates']) : NULL;
    @endphp

    @if (!$allowed)
        <div class="text-center py-3"><i>{{ translator('You are not allowed to see the certificates!') }}</i></div>
    @elseif ($variant === 'card')
        {{-- ============ CARD (dashboard) ============ --}}
        <div class="d-flex gap-2 mb-2">
            @foreach ($stateLabels as $state => $label)
                <div class="border px-2 py-1 flex-fill text-center">
                    <div class="fs-5 fw-bold lh-1">{{ $counts[$state] }}</div>
                    <div class="small">@include($badge, ['state' => $state, 'text' => $label])</div>
                </div>
            @endforeach
        </div>
        @forelse ($rows as $row)
            <div class="d-flex align-items-center gap-2 small py-1 border-bottom" wire:key="cov-card-{{ $loop->index }}">
                @include($badge, ['state' => $row['state'], 'text' => ''])
                <div class="text-truncate flex-fill" title="{{ $row['worker'] }} - {{ translator($row['title']) }}">
                    <b>{{ $row['worker'] }}</b> &middot; {{ translator($row['title']) }}
                </div>
                <div class="text-nowrap text-muted">{{ $row['state'] === 'missing' ? translator('Missing') : $daysText($row) }}</div>
            </div>
        @empty
            <div class="text-center small text-muted py-2"><i>{{ translator('Everything is up to date.') }}</i></div>
        @endforelse
        @if ($total > 0)
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-sm btn-light gray-border-color no-border-radius shadow"
                    data-trigger="global-modal" data-component="certificates-overview" data-params='@json(['variant' => 'full', 'days' => $days])'>
                    {{ translator('Show all') }} ({{ $total }}) <i class="bi bi-box-arrow-up-right"></i>
                </button>
            </div>
        @endif
    @else
        {{-- ============ FULL (global modal) ============ --}}
        <x-ui.card :border=FALSE :noBodyPadding=TRUE loading="states, type, workplace, search, days, resetFilters">
            {{-- Counts, also the status filter --}}
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach ($stateLabels as $state => $label)
                    <label class="border px-2 py-1 d-flex align-items-center gap-2 mb-0 @if (!in_array($state, $states, TRUE)) opacity-50 @endif" style="cursor: pointer" wire:key="cov-state-{{ $state }}">
                        <input type="checkbox" class="form-check-input m-0" value="{{ $state }}" wire:model.live="states">
                        @include($badge, ['state' => $state, 'text' => $label])
                        <span class="fw-bold">{{ $counts[$state] }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Filters --}}
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm no-border-radius" placeholder="{{ translator('Search worker, name, workplace') }}" wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm no-border-radius" wire:model.live="type">
                        <option value="">{{ translator('All types') }}</option>
                        @foreach ($types as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm no-border-radius" wire:model.live="workplace">
                        <option value="">{{ translator('All workplaces') }}</option>
                        @foreach ($workplaces as $id => $key) <option value="{{ $id }}">{{ $key }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm no-border-radius" wire:model.live="days" title="{{ translator('Due soon') }}">
                        @foreach ([30, 60, 90, 180] as $option) <option value="{{ $option }}">{{ translator('Due within') }} {{ $option }} {{ translator('days') }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex">
                    <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" wire:click="resetFilters" title="{{ translator('Reset the filters') }}" />
                </div>
            </div>

            {{-- Rows --}}
            <div style="max-height: 60vh; overflow: auto">
                <table class="table table-sm align-middle mb-0">
                    <thead style="position: sticky; top: 0; z-index: 1">
                        <tr>
                            <th class="bg-white">{{ translator('Worker') }}</th>
                            <th class="bg-white">{{ translator('Workplace') }}</th>
                            <th class="bg-white">{{ translator('Type') }}</th>
                            <th class="bg-white">{{ translator('Name') }}</th>
                            <th class="bg-white">{{ translator('Valid until') }}</th>
                            <th class="bg-white">{{ translator('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr wire:key="cov-row-{{ $row['workerId'] }}-{{ $row['type'] }}-{{ $loop->index }}">
                                <td class="text-nowrap">
                                    @if ($url = $workerUrl($row))
                                        <a href="{{ $url }}" title="{{ translator('Open the worker') }}">{{ str_pad($row['workerId'], 3, '0', STR_PAD_LEFT) }} {{ $row['worker'] }}</a>
                                    @else
                                        {{ str_pad($row['workerId'], 3, '0', STR_PAD_LEFT) }} {{ $row['worker'] }}
                                    @endif
                                </td>
                                <td class="small">{{ $row['workplace'] ?? '—' }}</td>
                                <td class="small">{{ $types[$row['type']] ?? $row['type'] }}</td>
                                <td>
                                    {{ translator($row['title']) }}
                                    @if ($row['required']) <i class="bi bi-diagram-3 text-muted" title="{{ translator('Required by the workplace') }}"></i> @endif
                                </td>
                                <td>{{ $row['validUntil']?->format('d.m.Y') ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @include($badge, ['state' => $row['state']])
                                    <small class="text-muted">{{ $row['state'] === 'missing' ? $row['reason'] : $daysText($row) }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-3"><i>{{ $total === 0 ? translator('Everything is up to date.') : translator('Nothing matches the filters.') }}</i></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
