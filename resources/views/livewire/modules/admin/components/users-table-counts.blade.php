{{-- Users table toolbar (left start): number of active / inactive users --}}
<div class="d-flex align-items-center gap-1 small">
    <span class="badge text-bg-success no-border-radius" title="{{ translator('Active') }}">{{ $counts['active'] }}</span>
    <span class="badge text-bg-secondary no-border-radius" title="{{ translator('Inactive') }}">{{ $counts['inactive'] }}</span>
</div>
