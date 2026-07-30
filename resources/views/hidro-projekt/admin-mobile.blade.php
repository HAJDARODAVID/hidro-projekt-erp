@extends('layouts.mobile.app-admin')

@section('content')

    {{-- Quick links --}}
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

    {{-- My tasks --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-list-check me-1"></i> {{ __('Moji zadaci') }}</span>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($myTasks as $task)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>{{ $task->task }}</div>
                        @php
                            $priorityBadge = match ($task->priority) {
                                \App\Models\ToDoList::PRIORITY_HIGH => 'bg-danger-subtle text-danger-emphasis',
                                \App\Models\ToDoList::PRIORITY_MEDIUM => 'bg-warning-subtle text-warning-emphasis',
                                default => 'bg-secondary-subtle text-secondary-emphasis',
                            };
                        @endphp
                        <span class="badge {{ $priorityBadge }}">{{ \App\Models\ToDoList::PRIORITY_HR[$task->priority] }}</span>
                    </div>
                    @if ($task->deadline)
                        <div class="text-muted small mt-1"><i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($task->deadline)->format('d.m.Y.') }}</div>
                    @endif
                </div>
            @empty
                <div class="list-group-item text-muted small">{{ __('Nemate aktivnih zadataka.') }}</div>
            @endforelse
        </div>
    </div>

@endsection
