@php
    $deviceIcon = ['desktop' => 'display', 'phone' => 'phone', 'tablet' => 'tablet'];
@endphp
<div class="d-flex flex-column h-100" wire:poll.60s>
    {{-- Headline --}}
    <div class="d-flex align-items-end gap-3 px-3 py-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px"></span>
                <span class="fs-3 fw-bold lh-1">{{ $users->count() }}</span>
            </div>
            <div class="text-muted" style="font-size: .72rem">{{ translator('online') }} ({{ translator('last') }} {{ $window }} min)</div>
        </div>
        <div class="ms-auto d-flex gap-3 text-end small">
            <div>
                <div class="fw-semibold">{{ $loginsToday }}</div>
                <div class="text-muted" style="font-size: .72rem">{{ translator('logins today') }}</div>
            </div>
            <div>
                <div class="fw-semibold @if($failedLogin) text-danger @endif">{{ $failedLogin }}</div>
                <div class="text-muted" style="font-size: .72rem">{{ translator('failed logins') }}</div>
            </div>
        </div>
    </div>

    {{-- Device split --}}
    @if ($users->count())
        <div class="d-flex gap-3 px-3 py-1 border-bottom small text-muted">
            @foreach ($devices as $device => $count)
                <span><i class="bi bi-{{ $deviceIcon[$device] ?? 'question' }}"></i> {{ $count }} {{ translator($device) }}</span>
            @endforeach
        </div>
    @endif

    {{-- Users --}}
    <div class="flex-fill" style="overflow-y: auto">
        @forelse ($users as $user)
            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                <div class="rounded-circle bg-body-secondary d-flex align-items-center justify-content-center flex-shrink-0 small fw-semibold" style="width: 30px; height: 30px">
                    {{ collect(explode(' ', $user['name']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                </div>
                <div class="flex-fill" style="min-width: 0">
                    <div class="d-flex justify-content-between gap-2 small">
                        <span class="fw-semibold text-truncate">{{ $user['name'] }}</span>
                        <span class="text-muted text-nowrap">{{ $user['last']->diffForHumans(short: TRUE) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2 text-muted" style="font-size: .75rem">
                        <span class="text-truncate">{{ $user['role'] }} · {{ $user['page'] }}</span>
                        <i class="bi bi-{{ $deviceIcon[$user['device']] ?? 'question' }}" title="{{ translator($user['device']) }}"></i>
                    </div>
                </div>
            </div>
        @empty
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted py-4">
                <i class="bi bi-moon-stars fs-2"></i>
                <div class="small mt-1">{{ translator('Nobody is online.') }}</div>
            </div>
        @endforelse
    </div>
</div>
