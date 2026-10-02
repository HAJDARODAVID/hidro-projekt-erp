<div>
    <x-ui.card :noBodyPadding=TRUE loading="confirmBtn, createAnyway, activateWorker" :border=FALSE>
        <div class="d-flex gap-3 flex-wrap px-1">
            <div class="flex-grow-1">
                <x-ui.input
                    label="{{ translator('First name') }}"
                    placeholder="{{ translator('First name') }}"
                    wModel="firstName"
                    maxlength="255"
                    wire:keydown.enter="confirmBtn"
                    autofocus
                />
            </div>
            <div class="flex-grow-1">
                <x-ui.input
                    label="{{ translator('Last name') }}"
                    placeholder="{{ translator('Last name') }}"
                    wModel="lastName"
                    maxlength="255"
                    wire:keydown.enter="confirmBtn"
                />
            </div>
        </div>

        @if (!empty($existingWorkers))
            {{-- A worker with the same name already exists: create a new one anyway or activate an inactive one --}}
            <div class="alert alert-warning no-border-radius mt-3 mb-0 mx-1">
                <div class="fw-bold mb-2">{{ translator('A worker with this name already exists!') }}</div>
                <div class="mb-2">{{ translator('Do you want to create a new worker anyway or activate an inactive worker?') }}</div>
                <div class="d-flex flex-column gap-2">
                    @foreach ($existingWorkers as $worker)
                        @php $isActive = $worker['status'] == \App\Models\Employees\SubcontractorWorker::STATUS_ACTIVE; @endphp
                        <div class="d-flex justify-content-between align-items-center gap-2" wire:key="existing-worker-{{ $worker['id'] }}">
                            <div class="d-flex gap-2 align-items-center">
                                <div>{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</div>
                                <x-v-divider px=0 />
                                <div>{{ $worker['firstName'] }} {{ $worker['lastName'] }}</div>
                                <span @class(['badge', 'bg-success' => $isActive, 'bg-danger' => !$isActive])>{{ translator($statuses[$worker['status']] ?? 'Inactive') }}</span>
                            </div>
                            @if (!$isActive)
                                <x-ui.btn type="suc.sm" icon="person-check" text="{{ translator('Activate') }}" action="activateWorker" param="{{ $worker['id'] }}" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end pt-3 px-1">
            @if (empty($existingWorkers))
                <x-ui.btn type="suc" icon="person-plus" text="{{ translator('Create') }}" action="confirmBtn" />
            @else
                <x-ui.btn type="war" icon="person-plus" text="{{ translator('Create anyway') }}" action="createAnyway" />
            @endif
        </div>
    </x-ui.card>
</div>
