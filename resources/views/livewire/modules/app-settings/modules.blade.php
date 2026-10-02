<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>

    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <div class="row flex-fill g-0 gap-3">
            <div class="col-md-3 d-flex flex-column">
                <x-ui.card title="Registered app modules" class="flex-fill d-flex flex-column">
                    <x-slot:headerActions>
                        <div class="d-flex gap-2">
                            {{-- Local: export the modules into the sync file / other envs: install the sync file --}}
                            @if ($isLocalEnv)
                                <x-ui.btn
                                    type="lig.sm"
                                    icon="box-arrow-up"
                                    action="exportModules"
                                    title="{{ translator('Export modules and routes to the sync file') }}"
                                    wire:loading.attr="disabled"
                                    wire:target="exportModules"
                                />
                            @else
                                <x-ui.btn
                                    type="lig.sm"
                                    icon="arrow-repeat"
                                    action="syncModulesFromFile"
                                    :disabled="$syncFileDate === NULL"
                                    title="{{ $syncFileDate ? translator('Sync modules and routes from the file') . ' (' . $syncFileDate . ')' : translator('The sync file does not exist') }}"
                                    wire:confirm="{{ translator('Sync the modules and routes from the file? Existing modules and routes are updated, new ones are created.') }}"
                                    wire:loading.attr="disabled"
                                    wire:target="syncModulesFromFile"
                                />
                            @endif
                            @livewire('modules.app-settings.components.create-new-app-module-modal')
                        </div>
                    </x-slot:headerActions>
                    <x-ui.input size="sm" placeholder="Search" wModel="moduleSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                        @if ($moduleSearch != NULL || $moduleSearch != "")
                            <x-slot:append>
                                <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetModuleSearchInput" />
                            </x-slot:append>
                        @endif
                    </x-ui.input>
                    <hr class="m-0 my-2">   
                    <div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 63px;margin-bottom: 10px; overflow-y: auto">
                        @isset($appModules)
                            <x-ui.list-group>
                                @foreach ($appModules as $key => $module)
                                    @php $isSelected = $module['id'] == $selectedAppModule ? TRUE : FALSE; @endphp
                                    <x-ui.list-item :selected=$isSelected wClickMethod="selectAppModule" wClickParam="{{ $module['id'] }}">
                                        <x-slot:slotLeft>{{ $module['name'] }}</x-slot:slotLeft>
                                        <x-slot:slotRight>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.change='appModules.{{ $key }}.active' onclick="event.stopPropagation();" />
                                            </div>
                                        </x-slot:slotLeft>
                                    </x-ui.list-item>
                                @endforeach 
                            </x-ui.list-group>
                        @endisset
                    </div>
                    
                </x-ui.card>
            </div>
            <div class="col-md d-flex flex-column">
                <x-ui.card title="Module settings" class="flex-fill d-flex flex-column" headerHight=48 loading='selectAppModule'>
                    @if ($selectedAppModule)
                        @livewire('modules.app-settings.components.module-basic-info-table', ['moduleID' => $selectedAppModule],key($selectedAppModule.now()))
                        <hr class="m-0 my-2">
                        @livewire('modules.app-settings.module-routes', ['moduleID' => $selectedAppModule],key('module-routes'.$selectedAppModule.now()))
                    @else
                        <div class="d-flex justify-content-center"><div class="py-3"><i>No module selected!</i></div></div>
                    @endif
                </x-ui.card>
            </div>
        </div>
        
    </x-ui.card>
</div>
