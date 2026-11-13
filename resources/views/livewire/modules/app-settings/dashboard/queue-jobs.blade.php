<div class="d-flex flex-column h-100">
    {{-- Stat tiles --}}
    <div class="row g-0 border-bottom text-center">
        @foreach ([
            ['label' => 'Pending',         'value' => $totals['pending'],   'icon' => 'hourglass-split'],
            ['label' => 'Workers',         'value' => $totals['workers'],   'icon' => 'gear-wide-connected'],
            ['label' => 'Processed (24h)', 'value' => number_format($totals['processed'], 0, ',', '.'), 'icon' => 'check2-all'],
            ['label' => 'Failed',          'value' => $totals['failed'],    'icon' => 'x-circle', 'danger' => $totals['failed'] > 0],
        ] as $stat)
            <div class="col-3 py-2 @if(!$loop->last) border-end @endif">
                <div class="fs-5 fw-bold lh-1 @if($stat['danger'] ?? FALSE) text-danger @endif">{{ $stat['value'] }}</div>
                <div class="text-muted mt-1" style="font-size: .72rem"><i class="bi bi-{{ $stat['icon'] }}"></i> {{ translator($stat['label']) }}</div>
            </div>
        @endforeach
    </div>

    <div class="flex-fill" style="overflow-y: auto">
        {{-- Per queue --}}
        <table class="table table-sm table-hover small mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">{{ translator('Queue') }}</th>
                    <th class="text-end">{{ translator('Pending') }}</th>
                    <th class="text-end">{{ translator('Workers') }}</th>
                    <th class="text-end">{{ translator('Wait') }}</th>
                    <th class="text-end pe-3">{{ translator('Processed') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($queues as $q)
                    <tr>
                        <td class="ps-3 font-monospace">{{ $q['name'] }}</td>
                        <td class="text-end">{{ $q['pending'] }}</td>
                        <td class="text-end">
                            @if ($q['workers'] === 0)
                                <span class="text-danger" title="{{ translator('No worker is listening on this queue') }}"><i class="bi bi-exclamation-triangle-fill"></i> 0</span>
                            @else
                                {{ $q['workers'] }}
                            @endif
                        </td>
                        <td class="text-end">{{ $q['wait'] }}</td>
                        <td class="text-end pe-3">{{ number_format($q['processed'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Failed jobs --}}
        <div class="px-3 pt-3 pb-1 small fw-semibold text-muted text-uppercase" style="font-size: .7rem">{{ translator('Failed jobs') }}</div>
        @forelse ($failed as $job)
            <div class="d-flex align-items-start gap-2 px-3 py-2 border-top" wire:key="job-{{ $tileId }}-{{ $job['id'] }}">
                <i class="bi bi-x-circle-fill text-danger mt-1"></i>
                <div class="flex-fill" style="min-width: 0">
                    <div class="d-flex justify-content-between gap-2 small">
                        <span class="fw-semibold text-truncate" title="{{ $job['job'] }}">{{ class_basename($job['job']) }}</span>
                        <span class="text-muted text-nowrap">{{ $job['failed_at']->diffForHumans() }}</span>
                    </div>
                    <div class="small text-truncate" title="{{ $job['error'] }}">{{ $job['error'] }}</div>
                    <div class="text-muted font-monospace" style="font-size: .72rem">{{ $job['queue'] }} · #{{ $job['id'] }}</div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <x-ui.btn type="lig.sm" icon="arrow-repeat" action="retry" param="{{ $job['id'] }}" title="{{ translator('Retry') }}" />
                    <x-ui.btn type="lig.sm" icon="trash" action="forget" param="{{ $job['id'] }}" title="{{ translator('Delete') }}"
                        wire:confirm="{{ translator('Delete this failed job?') }}" />
                </div>
            </div>
        @empty
            <div class="px-3 py-3 small text-muted border-top"><i class="bi bi-check-circle text-success"></i> {{ translator('No failed jobs.') }}</div>
        @endforelse
    </div>
</div>
