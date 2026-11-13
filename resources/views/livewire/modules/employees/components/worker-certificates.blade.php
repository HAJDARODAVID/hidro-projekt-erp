<div class="d-flex flex-column flex-fill">
    @php
        /**Status badge (same look as the overview) and the status labels */
        $badge = 'livewire.modules.employees.components.partials.certificate-state-badge';
        $stateLabels = [
            'valid' => translator('Valid'), 'due-soon' => translator('Due soon'), 'expired' => translator('Expired'),
            'no-expiry' => translator('No expiry'), 'missing' => translator('Missing'),
        ];
        $daysText = function ($row) {
            $days = $row->daysLeft();
            if ($days === NULL) return '';
            if ($days < 0) return translator('overdue') . ' ' . abs($days) . ' ' . translator('days');
            if ($days === 0) return translator('today');
            return translator('in') . ' ' . $days . ' ' . translator('days');
        };
    @endphp

    {{-- ============ Required by the workplace ============ --}}
    <h6 class="fw-bold mb-2">{{ translator('Required by the workplace') }}</h6>
    @if (empty($requirements))
        <div class="small text-muted mb-3">
            {{ translator('The workplace of the worker requires nothing (or the worker has no workplace).') }}
            {{ translator('Set it in Employees - Workplaces - Requirements: medical check every [months], safety training, licences.') }}
        </div>
    @else
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach ($requirements as $requirement)
                @php $label = $stateLabels[$requirement['state']] ?? $requirement['state']; $entry = $requirement['entry']; @endphp
                <div class="border px-2 py-1 d-flex align-items-center gap-2" style="min-width: 220px" wire:key="cert-req-{{ $requirement['type'] }}-{{ $loop->index }}">
                    @include($badge, ['state' => $requirement['state'], 'text' => ''])
                    <div class="small lh-sm flex-fill">
                        <div class="fw-semibold">{{ translator($requirement['title']) }}</div>
                        <div class="text-muted">
                            @if ($entry && $entry->valid_until) {{ translator('until') }} {{ $entry->valid_until->format('d.m.Y') }} ({{ $daysText($entry) }})
                            @elseif ($entry) {{ $label }}
                            @else {{ $label }} &middot; {{ $requirement['reason'] }}
                            @endif
                        </div>
                    </div>
                    @if ($canEdit && $requirement['state'] === 'missing')
                        <x-ui.btn type="lig.sm" icon="plus-lg" wire:click="newEntry('{{ $requirement['type'] }}', {{ \Illuminate\Support\Js::from($requirement['title']) }})"
                            title="{{ translator('Add') }}" />
                    @elseif ($canEdit && $entry && in_array($requirement['state'], ['expired', 'due-soon'], TRUE))
                        @include('livewire.modules.employees.components.partials.renew-certificate-btn', ['certificateId' => $entry->id])
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ============ Form ============ --}}
    @if ($showForm && $canEdit)
        <div class="border bg-light p-2 mb-3">
            <div class="fw-semibold small mb-2">{{ $editingId ? translator('Change the entry') : translator('New entry') }}</div>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small mb-1" for="cert-type">{{ translator('Type') }}</label>
                    <select id="cert-type" class="form-select form-select-sm no-border-radius" wire:model.live="form.type">
                        @foreach ($types as $type => $label) <option value="{{ $type }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                @if (!in_array($form['type'], $oneOfAKind, TRUE))
                    <div class="col-md-3">
                        <label class="small mb-1" for="cert-title">{{ translator('Name') }}<span class="text-danger">*</span></label>
                        <input id="cert-title" type="text" class="form-control form-control-sm no-border-radius" list="cert-title-suggestions" maxlength="255"
                            placeholder="{{ translator('e.g. Vozačka C') }}" wire:model="form.title">
                        <datalist id="cert-title-suggestions">@foreach ($suggestions as $suggestion) <option value="{{ $suggestion }}"></option> @endforeach</datalist>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="small mb-1" for="cert-issued">{{ translator('Done / issued on') }}</label>
                    <input id="cert-issued" type="date" class="form-control form-control-sm no-border-radius" wire:model.blur="form.issued_at">
                </div>
                <div class="col-md-2">
                    <label class="small mb-1" for="cert-valid">{{ translator('Valid until') }}</label>
                    <input id="cert-valid" type="date" class="form-control form-control-sm no-border-radius" wire:model="form.valid_until" title="{{ translator('Empty = does not expire') }}">
                </div>
                <div class="col">
                    <label class="small mb-1" for="cert-note">{{ translator('Note') }}</label>
                    <input id="cert-note" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.note">
                </div>
                <div class="col-auto d-flex gap-1">
                    <x-ui.btn type="suc.sm" icon="save" text="{{ translator('Save') }}" wire:click="saveEntry" />
                    <x-ui.btn type="lig.sm" icon="x-lg" wire:click="cancelForm" title="{{ translator('Cancel') }}" />
                </div>
            </div>
            <small class="text-muted">{{ translator('To renew an entry use "Renew" on it, the old version goes to the history. Change = correct a mistake (no history).') }} {{ translator('Empty "valid until" = does not expire.') }}</small>
        </div>
    @endif

    {{-- ============ All entries ============ --}}
    <div class="d-flex align-items-center gap-2 mb-1">
        <h6 class="fw-bold mb-0">{{ translator('Check-ups, trainings and certificates') }}</h6>
        @if ($historyCount > 0)
            <div class="form-check form-switch m-0 ms-2">
                <input class="form-check-input" type="checkbox" id="cert-history" wire:model.live="showHistory">
                <label class="form-check-label small" for="cert-history">{{ translator('Show history') }} ({{ $historyCount }})</label>
            </div>
        @endif
        @if ($canEdit && !$showForm)
            <div class="ms-auto"><x-ui.btn type="suc.sm" icon="plus-lg" text="{{ translator('New entry') }}" wire:click="newEntry" /></div>
        @endif
    </div>

    {{-- Scroll area: many entries don't make the card taller --}}
    <div class="flex-fill position-relative" style="min-height: 200px">
        <div class="position-absolute top-0 start-0 end-0 bottom-0 overflow-auto">
            <table class="table table-sm align-middle mb-0">
                <thead style="position: sticky; top: 0; z-index: 1">
                    <tr>
                        <th class="bg-white">{{ translator('Type') }}</th>
                        <th class="bg-white">{{ translator('Name') }}</th>
                        <th class="bg-white">{{ translator('Done / issued on') }}</th>
                        <th class="bg-white">{{ translator('Valid until') }}</th>
                        <th class="bg-white">{{ translator('Status') }}</th>
                        <th class="bg-white">{{ translator('Note') }}</th>
                        <th class="bg-white" style="width: 120px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $row)
                        <tr class="@if (!$row->current) text-muted @endif @if ($editingId === $row->id) table-active @endif" wire:key="cert-row-{{ $row->id }}">
                            <td class="small">{{ $types[$row->type] ?? $row->type }}</td>
                            <td>{{ translator($row->title) }}</td>
                            <td>{{ $row->issued_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="@if ($row->current && in_array($row->state, ['expired', 'due-soon'], TRUE)) fw-bold @endif">{{ $row->valid_until?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                @if ($row->current)
                                    @include($badge, ['state' => $row->state])
                                    <small class="text-muted">{{ $daysText($row) }}</small>
                                @else
                                    <span class="badge bg-light text-muted border no-border-radius">{{ translator('Replaced') }}</span>
                                @endif
                            </td>
                            <td class="small text-truncate" style="max-width: 220px" title="{{ $row->note }}">{{ $row->note }}</td>
                            <td class="text-end text-nowrap">
                                @if ($canEdit)
                                    <div class="d-inline-flex gap-1">
                                        <x-ui.btn type="lig.sm" icon="pencil" wire:click="editEntry({{ $row->id }})" title="{{ translator('Change (correct a mistake)') }}" />
                                        @include('livewire.modules.employees.components.partials.renew-certificate-btn', ['certificateId' => $row->id])
                                        <x-ui.btn type="dan.sm" icon="trash" wire:click="deleteEntry({{ $row->id }})" title="{{ translator('Delete') }}"
                                            wire:confirm="{{ translator('Delete the entry? It is kept in the history.') }}" />
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-3"><i>{{ translator('No check-ups, trainings or certificates yet.') }}</i></td></tr>
                    @endforelse

                    {{-- History (worker_certificate_history): renewed / deleted versions, newest first --}}
                    @if ($showHistory && $history->isNotEmpty())
                        <tr><td colspan="7" class="bg-light small fw-semibold py-1">{{ translator('History') }}</td></tr>
                        @foreach ($history as $old)
                            <tr class="text-muted" wire:key="cert-history-{{ $old->id }}">
                                <td class="small">{{ $types[$old->type] ?? $old->type }}</td>
                                <td>{{ translator($old->title) }}</td>
                                <td>{{ $old->issued_at?->format('d.m.Y') ?? '—' }}</td>
                                <td>{{ $old->valid_until?->format('d.m.Y') ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @if ($old->action === \App\Models\Employees\WorkerCertificateHistory::ACTION_DELETED)
                                        <span class="badge bg-light text-danger border no-border-radius"><i class="bi bi-trash"></i> {{ translator('Deleted') }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border no-border-radius"><i class="bi bi-arrow-repeat"></i> {{ translator('Renewed') }}</span>
                                    @endif
                                    <small>{{ $old->archived_at?->format('d.m.Y') }}</small>
                                </td>
                                <td class="small text-truncate" style="max-width: 220px" title="{{ $old->note }}">{{ $old->note }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <small class="text-muted mt-2">{{ translator('Due soon') }} = {{ translator('within') }} {{ $dueSoonDays }} {{ translator('days') }}.</small>
</div>
