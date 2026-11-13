<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>

    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <div class="row flex-fill g-0 gap-3">
            <div class="col-md-3 d-flex flex-column">
                <x-ui.card title="{{ translator('Workers') }}" class="flex-fill d-flex flex-column">
                    <x-slot:headerActions>
                        @if ($canCreate)
                            <div title="{{ translator('New worker') }}">
                                <x-ui.modal.modal-open-btn btnType="suc.sm" icon="person-plus" targetComponent="create-worker" />
                            </div>
                        @else
                            <div title="{{ translator('You are not allowed to create workers!') }}">
                                <x-ui.btn type="suc.sm" icon="person-plus" :disabled=TRUE />
                            </div>
                        @endif
                    </x-slot:headerActions>
                    <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="workerSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                        @if ($workerSearch != NULL || $workerSearch != "")
                            <x-slot:append>
                                <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetWorkerSearchInput" />
                            </x-slot:append>
                        @endif
                    </x-ui.input>
                    <hr class="m-0 my-2">
                    <div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 63px;margin-bottom: 10px; overflow-y: auto">
                        @isset($workers)
                        <x-ui.list-group>
                            @foreach ($workers as $key => $worker)
                            @php $isSelected = $worker['id'] == $selectedWorker ? TRUE : FALSE; @endphp
                            <x-ui.list-item :selected=$isSelected wClickMethod="selectWorker" wClickParam="{{ $worker['id'] }}">
                                <x-slot:slotLeft>
                                    <div class="d-flex gap-2">
                                        <div class="">{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</div>
                                        <x-v-divider px=0 />
                                        <div class="">{{ $worker['firstName'] }} {{ $worker['lastName'] }}</div>
                                    </div>
                                </x-slot:slotLeft>
                                <x-slot:slotRight>
                                    <x-ui.employees.status-indicator empID="{{ $worker['id'] }}" />
                                </x-slot:slotRight>
                            </x-ui.list-item>
                            @endforeach
                        </x-ui.list-group>
                        @endisset
                    </div>

                </x-ui.card>
            </div>
            <div class="col-md d-flex flex-column">
                <x-ui.card  class="flex-fill d-flex flex-column" headerHight=48 loading='selectWorker, selectTab'>
                    <x-slot:title>
                        <div class="d-flex gap-2 align-items-center">
                            @php $selectedWorkerData = $selectedWorker ? collect($workers)->firstWhere('id', $selectedWorker) : NULL; @endphp
                            <div class="">{{ translator('Worker info') }}@if ($selectedWorkerData): <span class="fw-bold">{{ $selectedWorkerData['firstName'] }} {{ $selectedWorkerData['lastName'] }}</span>@endif</div>
                            <x-v-divider class="align-self-stretch" />
                            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1/>
                        </div>
                        
                    </x-slot:title>
                    @if ($selectedWorker)
                        @switch ($activeTab)
                            @case ('payroll-info')
                                @livewire('modules.employees.components.worker-payroll-info', ['workerId' => $selectedWorker], key('worker-info-payroll-info-' . $selectedWorker))
                                @break
                            @case ('certificates')
                                @livewire('modules.employees.components.worker-certificates', ['workerId' => $selectedWorker], key('worker-info-certificates-' . $selectedWorker))
                                @break
                            @case ('user-right')
                                @livewire('modules.employees.components.worker-user-rights', ['workerId' => $selectedWorker], key('worker-info-user-right-' . $selectedWorker))
                                @break
                            @default
                                @livewire('modules.employees.components.basic-worker-info', ['workerId' => $selectedWorker], key('worker-info-basic-info-' . $selectedWorker))
                        @endswitch
                    @else
                        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No worker selected!') }}</i></div></div>
                    @endif
                </x-ui.card>
            </div>
        </div>
</div>

</x-ui.card>
</div>