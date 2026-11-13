@php
    $statusStyle = [
        'good'     => ['color' => 'success', 'icon' => 'check-circle-fill',         'label' => 'OK'],
        'warning'  => ['color' => 'warning', 'icon' => 'exclamation-triangle-fill', 'label' => 'Warning'],
        'critical' => ['color' => 'danger',  'icon' => 'x-octagon-fill',            'label' => 'Critical'],
    ];
    $w = $statusStyle[$worst];
@endphp
<div class="d-flex flex-column h-100">
    {{-- Overall status --}}
    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
        <i class="bi bi-{{ $w['icon'] }} text-{{ $w['color'] }}"></i>
        <span class="small fw-semibold">
            @if ($worst === 'good') {{ translator('All systems normal') }}
            @else {{ translator('Needs attention') }} @endif
        </span>
        <span class="ms-auto small text-muted">{{ translator('dummy data') }}</span>
    </div>

    <div class="flex-fill" style="overflow-y: auto">
        {{-- Usage meters --}}
        <div class="px-3 pt-2">
            @foreach ($meters as $m)
                @php $s = $statusStyle[$m['status']]; @endphp
                <div class="mb-2" title="{{ translator($m['label']) }}: {{ $m['value'] }}% — {{ translator($s['label']) }}">
                    <div class="d-flex justify-content-between align-items-center small">
                        <span><i class="bi bi-{{ $m['icon'] }} text-muted me-1"></i>{{ translator($m['label']) }}</span>
                        <span class="d-flex align-items-center gap-1">
                            @if ($m['status'] !== 'good')
                                <i class="bi bi-{{ $s['icon'] }} text-{{ $s['color'] }}"></i>
                                <span class="text-{{ $s['color'] }}-emphasis">{{ translator($s['label']) }}</span>
                            @endif
                            <span class="fw-semibold ms-1">{{ $m['value'] }}%</span>
                        </span>
                    </div>
                    <div class="progress mt-1" style="height: 6px; border-radius: 3px" role="progressbar" aria-valuenow="{{ $m['value'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-{{ $s['color'] }}" style="width: {{ $m['value'] }}%; border-radius: 3px"></div>
                    </div>
                    <div class="text-muted" style="font-size: .72rem">{{ $m['detail'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Runtime info (real values) --}}
        <div class="px-3 pb-2">
            <hr class="my-2">
            <div class="row g-0 small">
                @foreach ($info as $label => $value)
                    <div class="col-6 d-flex justify-content-between pe-3 py-1 border-bottom border-light-subtle">
                        <span class="text-muted">{{ translator($label) }}</span>
                        <span class="fw-semibold text-truncate ms-2
                            @if ($label === 'Debug mode' && $value === 'ON' && app()->environment('production')) text-danger @endif">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
