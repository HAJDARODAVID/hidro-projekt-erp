{{--
    Status badge of a certificate / check (worker's tab and the overview use the same look).
    $state: valid | due-soon | expired | no-expiry | missing, optional $text (default: the status label)
--}}
@php
    $look = [
        'valid'     => ['bg-success', 'check-circle', 'Valid'],
        'due-soon'  => ['bg-warning text-dark', 'exclamation-triangle', 'Due soon'],
        'expired'   => ['bg-danger', 'x-circle', 'Expired'],
        'no-expiry' => ['bg-secondary', 'infinity', 'No expiry'],
        'missing'   => ['bg-danger', 'dash-circle', 'Missing'],
    ][$state] ?? ['bg-secondary', 'question-circle', $state];
@endphp
<span class="badge {{ $look[0] }} no-border-radius"><i class="bi bi-{{ $look[1] }}"></i>@if (($text ?? NULL) !== '') {{ $text ?? translator($look[2]) }}@endif</span>
