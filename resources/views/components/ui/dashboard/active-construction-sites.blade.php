{{-- Active construction sites --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-cone-striped me-1"></i> {{ __('Aktivna gradilišta') }}</span>
        <a href="{{ route('hp_constructionSites') }}" class="small text-decoration-none">{{ __('Sve') }} <i class="bi bi-chevron-right"></i></a>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($activeConstructionSites as $site)
            <a href="{{ route('hp_showConstructionSite', $site->id) }}" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold">{{ $site->name }}</div>
                        <div class="text-muted small">{{ $site->town }}</div>
                    </div>
                    <span class="badge bg-success-subtle text-success-emphasis">{{ \App\Models\ConstructionSiteModel::CONSTRUCTION_STATUS[$site->status] }}</span>
                </div>
            </a>
        @empty
            <div class="list-group-item text-muted small">{{ __('Trenutno nema aktivnih gradilišta.') }}</div>
        @endforelse
    </div>
</div>