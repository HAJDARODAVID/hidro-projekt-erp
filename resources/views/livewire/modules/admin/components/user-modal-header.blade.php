{{-- Header of the global modal admin-user --}}
<div class="d-flex align-items-center gap-2" style="min-width: 0">
    @if ($user)
        <i class="bi bi-person-circle fs-4"></i>
        <div style="min-width: 0">
            <div class="fw-semibold text-truncate">
                {{ $user->name }} {{ $user->last_name }}
                @if ($user->active)
                    <span class="badge text-bg-success no-border-radius">{{ translator('Active') }}</span>
                @else
                    <span class="badge text-bg-secondary no-border-radius">{{ translator('Inactive') }}</span>
                @endif
                @if ($isSelf) <span class="badge text-bg-light border no-border-radius">{{ translator('You') }}</span> @endif
            </div>
            <div class="small text-muted fw-normal">
                #{{ $user->id }}
                · {{ translator('Created') }}: {{ $user->created_at?->format('d.m.Y') ?? '–' }}
                · {{ translator('Last login') }}: {{ $user->last_login_at?->format('d.m.Y H:i') ?? translator('never') }}
            </div>
        </div>
    @else
        <i class="bi bi-person-plus fs-4"></i>
        <div class="fw-semibold">{{ translator('New user') }}</div>
    @endif
</div>
