<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <style>
        /* Status switch: green when active, red when inactive (white knob in both states) */
        .subcontractor-status-switch {
            cursor: pointer;
            background-color: #dc3545;
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
        }
        .subcontractor-status-switch:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .subcontractor-status-switch:focus {
            box-shadow: none;
        }
    </style>
    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <div class="row flex-fill g-0 gap-3">
            {{-- Subcontractor companies --}}
            <div class="col-md-3 d-flex flex-column">
                <x-ui.card title="{{ translator('Subcontractors') }}" class="flex-fill d-flex flex-column">
                    <x-slot:headerActions>
                        <x-ui.modal.modal-open-btn
                            btn-type="suc.sm"
                            icon="building-add"
                            target-component="create-subcontractor"
                        />
                    </x-slot:headerActions>
                    <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="subcontractorSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                        @if ($subcontractorSearch != NULL || $subcontractorSearch != "")
                            <x-slot:append>
                                <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetSubcontractorSearchInput" />
                            </x-slot:append>
                        @endif
                    </x-ui.input>
                    <hr class="m-0 my-2">
                    <div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 63px;margin-bottom: 10px; overflow-y: auto">
                        @if (empty($subcontractors))
                            <div class="text-center text-muted py-3"><i>{{ translator('No subcontractors found') }}</i></div>
                        @else
                            <x-ui.list-group>
                                @foreach ($subcontractors as $item)
                                    <x-ui.list-item :selected="$item['id'] == $selectedSubcontractor" wClickMethod="selectSubcontractor" wClickParam="{{ $item['id'] }}">
                                        @php $isActive = $item['status'] == \App\Models\Employees\Subcontractor::STATUS_ACTIVE; @endphp
                                        <x-slot:slotLeft>
                                            <div class="d-flex gap-2">
                                                <div class="">{{ str_pad($item['id'], 3, '0', STR_PAD_LEFT) }}</div>
                                                <x-v-divider px=0 />
                                                <div class="@if (!$isActive) text-muted @endif">{{ $item['name'] }}</div>
                                            </div>
                                        </x-slot:slotLeft>
                                        <x-slot:slotRight>
                                            <div class="d-flex gap-2 align-items-center">
                                                <div class="form-check form-switch m-0" wire:key="subcontractor-status-{{ $item['id'] }}-{{ $item['status'] }}">
                                                    <input
                                                        type="checkbox"
                                                        role="switch"
                                                        class="form-check-input subcontractor-status-switch"
                                                        title="{{ translator($isActive ? 'Active' : 'Inactive') }}"
                                                        @checked($isActive)
                                                        wire:click.stop="toggleSubcontractorStatus({{ $item['id'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="toggleSubcontractorStatus"
                                                    >
                                                </div>
                                            </div>
                                        </x-slot:slotRight>
                                    </x-ui.list-item>
                                @endforeach
                            </x-ui.list-group>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            {{-- Selected subcontractor --}}
            <div class="col-md d-flex flex-column">
                <x-ui.card class="flex-fill d-flex flex-column" headerHight=48 loading='selectSubcontractor, selectTab'>
                    <x-slot:title>
                        <div class="d-flex gap-2 align-items-center">
                            <div @class(['fw-bold' => $subcontractor])>{{ $subcontractor ? $subcontractor->name : translator('Subcontractor info') }}</div>
                            <x-v-divider />
                            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
                        </div>
                    </x-slot:title>
                    @if ($subcontractor)
                        <x-slot:headerActions>
                            <x-ui.modal.modal-open-btn
                                btn-type="suc.sm"
                                icon="person-plus"
                                target-component="create-subcontractor-worker"
                                :params="['subcontractorId' => $subcontractor->id]"
                            />
                        </x-slot:headerActions>
                        @switch ($activeTab)
                            @case ('workers')
                                @livewire(
                                    'modules.employees.components.subcontractor-workers',
                                    ['subcontractorId' => $subcontractor->id],
                                    key('subcontractor-workers-' . $subcontractor->id)
                                )
                                @break
                        @endswitch
                    @else
                        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No subcontractor selected!') }}</i></div></div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </x-ui.card>
</div>
