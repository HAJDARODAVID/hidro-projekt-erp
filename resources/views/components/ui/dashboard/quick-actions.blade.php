{{-- Quick actions --}}
<div class="row g-2 mb-3">
    <div class="col-3 text-center">
        <a href="{{ route('hp_allWorkers') }}" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm py-2"><i class="bi bi-people fs-5"></i></div>
            <div class="small mt-1">{{ __('Radnici') }}</div>
        </a>
    </div>
    <div class="col-3 text-center">
        <a href="{{ route('hp_bdeHome') }}" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm py-2"><i class="bi bi-journal-text fs-5"></i></div>
            <div class="small mt-1">{{ __('Rad. dan') }}</div>
        </a>
    </div>
    <div class="col-3 text-center">
        <a href="{{ route('hp_tickets') }}" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm py-2"><i class="bi bi-ticket-perforated fs-5"></i></div>
            <div class="small mt-1">{{ __('Zahtjevi') }}</div>
        </a>
    </div>
    <div class="col-3 text-center">
        <a href="{{ route('hp_billOverview') }}" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm py-2"><i class="bi bi-receipt fs-5"></i></div>
            <div class="small mt-1">{{ __('Računi') }}</div>
        </a>
    </div>
</div>