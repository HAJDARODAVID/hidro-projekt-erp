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