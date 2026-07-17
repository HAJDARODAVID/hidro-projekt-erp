<x-ui.card :noBodyPadding=TRUE loading="createNewDiary" :border=FALSE>
            <div class="row">
                <div class="col-md">
                    <x-ui.card title="{{ translator('Add new attendance') }}" style="min-height: 600px">
                        <div class="row">
                            <div class="col-md-4">
                                <x-ui.input
                                    type="date"
                                    label="{{ translator('Date') }}"
                                    class="form-control-sm"
                                    wModel="date"
                                    wModelEvent="change"
                                    :disabled='true'
                                />
                            </div>
                            <div class="col-md">
                                <x-ui-select
                                    :options=$workersOptionsItems
                                    label="{{ translator('Worker') }}"
                                    initOpt="{{ translator('Select worker') }}"
                                    size="sm"
                                    model="attendance.worker_id"
                                    :disabled='true'
                                />
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md">
                                <div class="d-flex gap-1 justify-content-center">
                                    <x-ui.employees.absence-btns :att='["size" => "sm"]'/>
                                    <x-v-divider />
                                    <div class="d-flex" style="width: 139px">
                                        <x-ui.input
                                            type="{{ $hourInputAtt['type'] ?? null }}"
                                            class="form-control-sm"
                                            wModel="hourInput"
                                            :disabled='$hourInputAtt["disabled"]'
                                            style="text-align: center;font-weight: bold; background-color: {{ $hourInputAtt['style']['background-color'] ?? null }}"
                                        />
                                        @if($hourInputAtt["delete"]) 
                                            <x-ui.btn type="dan.sm" icon="trash" action='resetHourInputAction' />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md">
                                
                                <div class="d-flex justify-content-center">
                                    @foreach (WorkdayTypes::bdeType() as $typeKey => $type)
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="">{{ $type }}</div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input " type="radio" wire:model.live="diaryInfo.workdayType" value="{{ $typeKey }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                               
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md">
                                <x-ui-select
                                    :options=$workersOptionsItems
                                    label="{{ translator('Worker') }}"
                                    initOpt="{{ translator('Select worker') }}"
                                    size="sm"
                                    model="attendance.worker_id"
                                    :disabled='$workersSelectAtt["disabled"] ?? false'
                                />
                            </div>
                        </div>
                        <hr>
                        
                        
                    </x-ui.card>
                </div>
                <div class="col-md">
                    
                    <x-ui.card title="PRISUSTVO" loading="addWorkerToAttendance, removeWorkerFromAttendance">
                        {{-- 
                        <div class="row"><div class="col-md-6 d-flex gap-2">
                            <x-ui.input placeholder="Traži..." size="sm" :removeAddOnXP=TRUE wire:model.live.debounce.250ms='workerSearch'> 
                                @if ($workerSearch != NULL || $workerSearch != "")
                                    <x-slot:append>
                                        <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetSearchInput" />
                                    </x-slot:append>
                                @endif
                                
                            </x-ui.input>
                            {{-- TODO: ADD search modal here --}}
                            {{-- <div class="vr"></div>
                            <x-ui.btn type="lig.sm" icon="search" /> 
                        </div></div>
                        <hr>
                        @if($workerSearch)
                            <div class="d-flex flex-wrap gap-2 my-2">
                                @foreach ($workers['myWorkers'] as $worker)
                                    <x-ui.btn type="lig.sm" wClickMethod="addWorkerToAttendance" wClickParam="{{$worker->id}}, {{$worker->fullName}}, myWorkers">{{ $worker->fullName }}</x-ui.btn>
                                @endforeach
                                @foreach ($workers['cooperators'] as $worker)
                                    <x-ui.btn type="lig.sm" wClickMethod="addWorkerToAttendance" wClickParam="{{$worker->id}}, {{$worker->fullName}}-{{ $worker->getCoOpInfo->name }}, cooperators">{{ $worker->fullName }}-{{ $worker->getCoOpInfo->name }} </x-ui.btn>
                                @endforeach
                            </div>
                            <hr>
                        @endif
                        @isset($attendance)
                            <table class="table table-sm" style="table-layout: auto;">
                                <thead>
                                    <th>Ime prezime</th>
                                    <th>Sati</th>
                                    <th style="white-space: nowrap;"></th>
                                </thead>
                                <tbody>
                                    @isset($attendance['myWorkers'])
                                        @foreach ($attendance['myWorkers'] as $workerID => $workerData)
                                            <tr>
                                                <td>{{ $workerData['name'] }}</td>
                                                <td><x-ui.input type="number" width=10 size="sm" wModel='attendance.myWorkers.{{ $workerID }}.attTime' /></td>
                                                <td style="white-space: nowrap;">
                                                    <x-ui.btn type="lig.sm" icon="trash" wClickMethod="removeWorkerFromAttendance" wClickParam="{{ $workerID }}, myWorkers"/>
                                                </td>
                                            </tr>
                                        @endforeach
                                        
                                    @endisset

                                    @isset($attendance['cooperators'])
                                        @foreach ($attendance['cooperators'] as $workerID => $workerData)
                                            <tr>
                                                <td>{{ $workerData['name'] }}</td>
                                                <td><x-ui.input type="number" width=10 size="sm" wModel='attendance.cooperators.{{ $workerID }}.attTime' /></td>
                                                <td style="white-space: nowrap;">
                                                    <x-ui.btn type="lig.sm" icon="trash" wClickMethod="removeWorkerFromAttendance" wClickParam="{{ $workerID }}, cooperators" />
                                                </td>
                                            </tr>
                                        @endforeach
                                        
                                    @endisset
                                    
                                </tbody>
                            </table>
                        @endisset
                        @empty($attendance)
                            <div class="d-flex justify-content-center"><i>Nema zapisa radnika u prisustvu</i></div>
                        @endempty
                         --}} 
                    </x-ui.card> 
                      
                </div>
            </div>
        </x-ui.card> 
