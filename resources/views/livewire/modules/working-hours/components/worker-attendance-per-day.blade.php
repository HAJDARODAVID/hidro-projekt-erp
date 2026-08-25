
<x-ui.card :noBodyPadding=TRUE loading="saveNewAttendanceAction" :border=FALSE>
    <div class="row">
        <div class="col-md-5">
            <x-ui.card title="{{ translator('Add new attendance') }}" style="">
                <div class="row">
                    <div class="col-md-4">
                        <x-ui-input
                            type="date" size="sm"
                            label="{{ translator('Date') }}"
                            model="params.date" :disabled='true'
                        />
                    </div>
                    <div class="col-md">
                        <x-ui-input
                            size="sm"
                            label="{{ translator('Worker') }}"
                            model="workerInfo.name" :disabled='true'
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
                <div class="row mt-2">
                    <div class="col-md">
                        
                        <div class="d-flex justify-content-center">
                            @foreach (WorkdayTypes::bdeType() as $typeKey => $type)
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="">{{ $type }}</div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input " type="radio" wire:model.live="attendance.type" value="{{ $typeKey }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md">
                        <x-ui-select
                            :options=$workDiaryOptionsItems
                            label="{{ translator('Workday diary') }}"
                            initOpt="{{ translator('w/o workday diary') }}"
                            size="sm"
                            model="attendance.working_day_record_id"
                        />
                    </div>
                </div>
                <hr>
                <div class="">
                    <label>{{ translator('Comment') }}</label>
                    <textarea class="form-control no-border-radius" style="width: 100%" rows="4" wire:model.blur='attendance.comment'></textarea>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <x-ui.btn icon="box-arrow-in-right" type="suc.sm" action="saveNewAttendanceAction" />
                </div>
            </x-ui.card>
        </div>
        <div class="col-md">
            
            <x-ui.card title="{{ translator('Existing attendance') }}" loading="addWorkerToAttendance, removeWorkerFromAttendance">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead>
                        <tr class="text-uppercase text-muted small">
                            <th style="width: 40px">#</th>
                            <th style="width: 380px">{{ translator('Workday diary') }}</th>
                            <th class="text-center" style="width: 70px">{{ translator('Hours') }}</th>
                            <th style="width: 68px" class="text-end">{{ translator('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Example rows, replace with bound attendance data --}}
                        @foreach ($attCollection as $att)
                            @php
                                $rowAbsenceType = $att->getAbsenceReason() ? \App\Models\Employees\AttendanceAbsenceType::setByType($att->getAbsenceReason()) : null;
                            @endphp
                           <tr>
                                <td class="text-muted">{{ $att->getId() }}</td>
                                <td>{{ $att->getConstructionSiteName() }}</td>
                                <td class="text-end">
                                    <x-ui-input
                                        size="sm"
                                        type="{{ $rowAbsenceType ? 'text' : 'number' }}"
                                        :class="['text-center', 'no-spinner']"
                                        style="width: 70px;{{ $rowAbsenceType ? ' font-weight: bold; background-color: ' . \App\Services\Attendance\AbsenceBtnObject::getBackgroundColorForType($rowAbsenceType) . ';' : '' }}"
                                        :disabled="(bool) $rowAbsenceType"
                                        value="{{ $rowAbsenceType ? $rowAbsenceType->shortDesc() : $att->getWorkingHours() }}"
                                    />
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <div class="position-relative d-inline-block" x-data="{ showComment: false }">
                                            <x-ui.btn type="lig.sm" icon="file-text" @click="showComment = !showComment" />
                                            <div
                                                x-show="showComment"
                                                @click.outside="showComment = false"
                                                x-transition
                                                class="tool-tip bootstrap-mimic-tooltip"
                                                style="left: auto; right: 0; white-space: normal;"
                                            >
                                                -
                                            </div>
                                        </div>
                                        <x-ui.btn type="dan.sm" icon="trash" />
                                    </div>
                                </td>
                            </tr> 
                        @endforeach
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>
</x-ui.card> 
