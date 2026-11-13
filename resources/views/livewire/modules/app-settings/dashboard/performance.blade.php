<div class="d-flex flex-column h-100">
    <style>
        .perf-bars { display: flex; align-items: flex-end; gap: 2px; height: 100%; }
        .perf-bar { flex: 1 1 0; display: flex; align-items: flex-end; height: 100%; cursor: default; }
        .perf-bar > span { display: block; width: 100%; min-height: 2px; background: var(--bs-primary); opacity: .75; border-radius: 4px 4px 0 0; transition: opacity .1s; }
        .perf-bar:hover > span { opacity: 1; }
    </style>

    {{-- Headline numbers --}}
    <div class="row g-0 border-bottom text-center">
        @foreach ([
            ['label' => 'Requests (24h)', 'value' => number_format($stats['requests'], 0, ',', '.')],
            ['label' => 'Avg response',   'value' => $stats['avg_ms'] . ' ms'],
            ['label' => 'p95 response',   'value' => number_format($stats['p95_ms'], 0, ',', '.') . ' ms'],
            ['label' => 'Error rate',     'value' => number_format($stats['error_rate'], 1, ',', '.') . ' %'],
        ] as $stat)
            <div class="col-3 py-2 @if(!$loop->last) border-end @endif">
                <div class="fs-6 fw-bold lh-1">{{ $stat['value'] }}</div>
                <div class="text-muted mt-1" style="font-size: .72rem">{{ translator($stat['label']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Requests per hour (single series, hover a bar for the value) --}}
    <div class="px-3 pt-2">
        <div class="d-flex justify-content-between small">
            <span class="fw-semibold">{{ translator('Requests per hour') }}</span>
            <span class="text-muted">{{ translator('peak') }} {{ number_format($max, 0, ',', '.') }}</span>
        </div>
        <div style="height: 90px" class="mt-1 border-bottom">
            <div class="perf-bars" role="img" aria-label="{{ translator('Requests per hour, last 24 hours') }}">
                @foreach ($hours as $h)
                    <div class="perf-bar" title="{{ $h['label'] }} — {{ number_format($h['requests'], 0, ',', '.') }} {{ translator('requests') }} · {{ translator('avg') }} {{ $h['avg_ms'] }} ms">
                        <span style="height: {{ round($h['requests'] / $max * 100, 1) }}%"></span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="d-flex justify-content-between text-muted" style="font-size: .7rem">
            <span>{{ $hours->first()['label'] }}</span>
            <span>{{ $hours[12]['label'] }}</span>
            <span>{{ $hours->last()['label'] }}</span>
        </div>
    </div>

    {{-- Slowest endpoints --}}
    <div class="flex-fill mt-2" style="overflow-y: auto">
        <table class="table table-sm table-hover small mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">{{ translator('Slowest endpoints') }}</th>
                    <th class="text-end">{{ translator('Avg') }}</th>
                    <th class="text-end">p95</th>
                    <th class="text-end pe-3">{{ translator('Hits') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($slowest as $row)
                    <tr>
                        <td class="ps-3 text-truncate" style="max-width: 0; width: 60%" title="{{ $row['method'] }} {{ $row['uri'] }}">
                            <span class="badge text-bg-light border font-monospace">{{ $row['method'] }}</span>
                            <span class="font-monospace">{{ $row['uri'] }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            @if ($row['avg'] >= 2000) <i class="bi bi-exclamation-triangle-fill text-warning" title="{{ translator('Slow') }}"></i> @endif
                            {{ number_format($row['avg'], 0, ',', '.') }} ms
                        </td>
                        <td class="text-end text-nowrap">{{ number_format($row['p95'], 0, ',', '.') }} ms</td>
                        <td class="text-end pe-3">{{ number_format($row['hits'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
