{{-- Quick stats --}}
<div class="row g-2 mb-3">
    <div class="col-6">
        <a href="{{ route('hp_constructionSites') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <i class="bi bi-cone-striped text-primary fs-4"></i>
                    <div class="fs-4 fw-bold mt-1">{{ $activeConstructionSitesCount }}</div>
                    <div class="text-muted small">{{ __('Aktivna gradilišta') }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="{{ route('hp_tickets') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <i class="bi bi-ticket-perforated text-warning fs-4"></i>
                    <div class="fs-4 fw-bold mt-1">{{ $openTicketsCount }}</div>
                    <div class="text-muted small">{{ __('Otvoreni zahtjevi') }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <i class="bi bi-list-check text-info fs-4"></i>
                <div class="fs-4 fw-bold mt-1">{{ $myTasksCount }}</div>
                <div class="text-muted small">{{ __('Moji zadaci') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <a href="{{ route('hp_billOverview') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <i class="bi bi-cash-coin text-success fs-4"></i>
                    <div class="fs-4 fw-bold mt-1">{{ number_format($monthlyExpenses, 0, ',', '.') }} €</div>
                    <div class="text-muted small">{{ __('Troškovi ovaj mjesec') }}</div>
                </div>
            </div>
        </a>
    </div>
</div>