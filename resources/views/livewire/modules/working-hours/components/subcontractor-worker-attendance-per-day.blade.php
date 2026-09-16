<x-ui.card :noBodyPadding=TRUE loading="saveNewAttendanceAction, attendance.date, attendance.worker_id" :border=FALSE>
    <div class="row">
        <div class="col-md-5">
            <x-ui.card title="{{ translator('Add new attendance') }}">
                <div class="row">
                    <div class="col-md-4">
                        @if ($selectWorker)
                            <x-ui-input
                                type="date" size="sm"
                                label="{{ translator('Date') }}"
                                model="attendance.date" event="change"
                            />
                        @else
                            <x-ui-input
                                type="date" size="sm"
                                label="{{ translator('Date') }}"
                                model="attendance.date" :disabled='true'
                            />
                        @endif
                    </div>
                    <div class="col-md">
                        <x-ui-input
                            size="sm"
                            label="{{ translator('Subcontractor') }}"
                            model="workerInfo.subcontractor" :disabled='true'
                        />
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md">
                        @if ($selectWorker)
                            <x-ui-select
                                :options=$workersOptionsItems
                                label="{{ translator('Worker') }}"
                                initOpt="{{ translator('Select worker') }}"
                                size="sm"
                                model="attendance.worker_id"
                            />
                        @else
                            <x-ui-input
                                size="sm"
                                label="{{ translator('Worker') }}"
                                model="workerInfo.name" :disabled='true'
                            />
                        @endif
                    </div>
                </div>
                <div class="row mt-2" wire:key="work-diary-{{ $attendance['date'] }}">
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
                <div class="row mt-2">
                    <div class="col-md">
                        <x-ui.input
                            type="number"
                            label="{{ translator('Hours') }}"
                            class="form-control-sm"
                            wModel="hourInput"
                            style="text-align: center;font-weight: bold;"
                        />
                    </div>
                    <div class="col-md">
                        <div class="d-flex justify-content-end align-items-end h-100">
                            <x-ui.btn icon="box-arrow-in-right" type="suc.sm" action="saveNewAttendanceAction" />
                        </div>
                    </div>
                </div>                
            </x-ui.card>
        </div>
        <div class="col-md">
            <x-ui.card title="{{ translator('Existing attendance') }}" loading="deleteAttendanceAction">
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
                        @forelse ($attCollection as $att)
                            <tr>
                                <td class="text-muted">{{ $att->getId() }}</td>
                                <td>@if($att->getConstructionSiteName()){{ $att->getConstructionSiteName() }} @else {{ translator('w/o workday diary') }} @endif</td>
                                <td class="text-end">
                                    <x-ui-input
                                        size="sm"
                                        type="number"
                                        :class="['text-center', 'no-spinner']"
                                        style="width: 70px;"
                                        :disabled="true"
                                        value="{{ $att->getWorkingHours() + 0 }}"
                                    />
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <x-ui.btn type="dan.sm" icon="trash" action="deleteAttendanceAction" param="{{ $att->getId() }}" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">
                                    <i>
                                        @if ($selectWorker && empty($attendance['worker_id']))
                                            {{ translator('Select a worker to see the attendance!') }}
                                        @else
                                            {{ translator('No attendance for this day!') }}
                                        @endif
                                    </i>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>
</x-ui.card>
