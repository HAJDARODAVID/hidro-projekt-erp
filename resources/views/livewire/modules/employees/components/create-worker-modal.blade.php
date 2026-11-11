{{-- Shown in the global modal (config/global-modal.php: create-worker) --}}
<div>
    @if (!$allowed)
        <div class="text-center py-3"><i>{{ translator('You are not allowed to create workers!') }}</i></div>
    @else
        <x-ui.card :noBodyPadding=TRUE :border=FALSE loading="confirmBtn, createAnyway">
            <div class="row g-3 px-1">
                {{-- Required --}}
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-name">{{ translator('First name') }}<span class="text-danger">*</span></label>
                    <input id="cw-name" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model.blur="form.name" wire:keydown.enter="confirmBtn" autofocus>
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-surname">{{ translator('Last name') }}<span class="text-danger">*</span></label>
                    <input id="cw-surname" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model.blur="form.surname" wire:keydown.enter="confirmBtn">
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-type">{{ translator('Worker type') }}<span class="text-danger">*</span></label>
                    <select id="cw-type" class="form-select form-select-sm no-border-radius" wire:model.live="form.type">
                        @foreach ($types as $type => $label) <option value="{{ $type }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-oib">{{ translator('OIB') }}</label>
                    <input id="cw-oib" type="text" inputmode="numeric" class="form-control form-control-sm no-border-radius" maxlength="14" placeholder="{{ translator('11 digits') }}" wire:model.blur="form.oib">
                </div>

                {{-- Contract --}}
                <div class="col-12">
                    <label class="small fw-semibold mb-1" for="cw-workplace">{{ translator('Workplace - contract') }}</label>
                    <select id="cw-workplace" class="form-select form-select-sm no-border-radius" wire:model="form.workplace">
                        <option value="">—</option>
                        @foreach ($workplaces as $workplace)
                            <option value="{{ $workplace->id }}" title="{{ $workplace->key }} - {{ $workplace->name }}">{{ \Illuminate\Support\Str::limit($workplace->key . ' - ' . $workplace->name, 80) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-doe">{{ translator('Date of employment') }}</label>
                    <input id="cw-doe" type="date" class="form-control form-control-sm no-border-radius" wire:model="form.doe">
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-ced">{{ translator('Contract expiration') }}</label>
                    <input id="cw-ced" type="date" class="form-control form-control-sm no-border-radius" wire:model="form.ced">
                </div>

                {{-- Contact --}}
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-phone">{{ translator('Phone nr') }}</label>
                    <input id="cw-phone" type="tel" class="form-control form-control-sm no-border-radius" maxlength="30" wire:model="form.phone">
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-email">{{ translator('E-mail') }}</label>
                    <input id="cw-email" type="email" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.email">
                </div>

                {{-- Address --}}
                <div class="col-md-8">
                    <label class="small fw-semibold mb-1" for="cw-street">{{ translator('Street') }}</label>
                    <input id="cw-street" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.street">
                </div>
                <div class="col-md-4">
                    <label class="small fw-semibold mb-1" for="cw-zip">{{ translator('ZIP') }}</label>
                    <input id="cw-zip" type="text" inputmode="numeric" class="form-control form-control-sm no-border-radius" maxlength="5" wire:model="form.zip">
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-town">{{ translator('Town') }}</label>
                    <input id="cw-town" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.town">
                </div>
                <div class="col-md-6">
                    <label class="small fw-semibold mb-1" for="cw-county">{{ translator('County') }}</label>
                    <input id="cw-county" type="text" class="form-control form-control-sm no-border-radius" maxlength="255" wire:model="form.county">
                </div>

                <div class="col-12">
                    <small class="text-muted">{{ translator('Payroll is entered in Worker info after the worker is created.') }}</small>
                </div>
            </div>

            @if (!empty($sameName))
                {{-- A worker with the same name exists: create a new one anyway? --}}
                <div class="alert alert-warning no-border-radius mt-3 mb-0 mx-1">
                    <div class="fw-bold mb-2">{{ translator('A worker with this name already exists!') }}</div>
                    <div class="d-flex flex-column gap-1 mb-2">
                        @foreach ($sameName as $worker)
                            @php $isActive = $worker['status'] == \App\Models\Employees\WorkerStatus::WORKER_STATUS_ACTIVE; @endphp
                            <div class="d-flex gap-2 align-items-center" wire:key="cw-same-{{ $worker['id'] }}">
                                <div>{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</div>
                                <x-v-divider px=0 />
                                <div>{{ $worker['firstName'] }} {{ $worker['lastName'] }}</div>
                                <span @class(['badge no-border-radius', 'bg-success' => $isActive, 'bg-danger' => !$isActive])>{{ translator($statuses[$worker['status']] ?? 'inactive') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="small">{{ translator('Is this a different person? Then create the worker anyway.') }}</div>
                </div>
            @endif

            <div class="d-flex justify-content-end pt-3 px-1">
                @if (empty($sameName))
                    <x-ui.btn type="suc" icon="person-plus" text="{{ translator('Create') }}" action="confirmBtn" />
                @else
                    <x-ui.btn type="war" icon="person-plus" text="{{ translator('Create anyway') }}" action="createAnyway" />
                @endif
            </div>
        </x-ui.card>
    @endif
</div>
