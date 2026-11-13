{{-- Shown in the global modal (config/global-modal.php: renew-certificate) --}}
<div>
    @if (!$allowed)
        <div class="text-center py-3"><i>{{ translator('You are not allowed to change the certificates!') }}</i></div>
    @elseif ($row === NULL)
        <div class="text-center py-3"><i>{{ translator('The entry does not exist.') }}</i></div>
    @else
        <x-ui.card :noBodyPadding=TRUE :border=FALSE loading="renew">
            {{-- The current version --}}
            <div class="border bg-light px-2 py-2 mb-3 small">
                <div class="fw-semibold mb-1">
                    {{ str_pad($row->worker_id, 3, '0', STR_PAD_LEFT) }} {{ $row->worker?->firstName }} {{ $row->worker?->lastName }}
                    &middot; {{ translator($row->title) }} <span class="text-muted fw-normal">({{ $types[$row->type] ?? $row->type }})</span>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span>{{ translator('Now') }}: {{ $row->issued_at?->format('d.m.Y') ?? '—' }} &rarr; {{ $row->valid_until?->format('d.m.Y') ?? translator('no expiry') }}</span>
                    @include('livewire.modules.employees.components.partials.certificate-state-badge', ['state' => $state])
                    @if ($row->note) <span class="text-muted text-truncate" style="max-width: 260px" title="{{ $row->note }}">{{ $row->note }}</span> @endif
                </div>
            </div>

            <div class="row g-3 px-1">
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="renew-start">{{ translator('Start date') }}<span class="text-danger">*</span></label>
                    <input id="renew-start" type="date" class="form-control form-control-sm no-border-radius" wire:model.blur="form.issued_at">
                    <small class="text-muted">{{ translator('Default: the current due date') }}</small>
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="renew-until">{{ translator('Due date') }}<span class="text-danger">*</span></label>
                    <input id="renew-until" type="date" class="form-control form-control-sm no-border-radius" wire:model="form.valid_until" autofocus>
                    <small class="text-muted">
                        {{ $row->type === 'medical' ? translator('Suggested from the interval of the workplace') : translator('Suggested: as long as the current one') }}
                    </small>
                </div>
                <div class="col-12">
                    <label class="small fw-semibold mb-1" for="renew-note">{{ translator('Notes') }}</label>
                    <input id="renew-note" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.note" wire:keydown.enter="renew">
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center pt-3 px-1">
                <x-ui.btn type="suc" icon="arrow-repeat" text="{{ translator('Renew') }}" action="renew" />
            </div>
        </x-ui.card>
    @endif
</div>
