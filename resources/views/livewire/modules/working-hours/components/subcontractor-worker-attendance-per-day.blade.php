<x-ui.card :noBodyPadding=TRUE loading="saveNewAttendanceAction, attendance.date, attendance.worker_id, dateTo" :border=FALSE>
    <div class="row">
        <div class="col-md-5">
            <x-ui.card title="{{ translator('Add new attendance') }}">
                <div class="row">
                    @if ($selectWorker)
                        <div class="col-md-4">
                            <x-ui-input
                                type="date" size="sm"
                                label="{{ translator('From') }}"
                                model="attendance.date" event="change"
                            />
                        </div>
                        <div class="col-md-4">
                            <x-ui-input
                                type="date" size="sm"
                                label="{{ translator('To') }}"
                                model="dateTo" event="change"
                            />
                        </div>
                        <div class="col-md d-flex align-items-end">
                            <div class="form-check form-switch m-0 mb-1" title="{{ translator('Skip Saturdays and Sundays when saving a date range') }}">
                                <input class="form-check-input" type="checkbox" id="skip-weekends" wire:model.live="skipWeekends">
                                <label class="form-check-label small" for="skip-weekends">{{ translator('Skip weekends') }}</label>
                            </div>
                        </div>
                    @else
                        <div class="col-md-4">
                            <x-ui-input
                                type="date" size="sm"
                                label="{{ translator('Date') }}"
                                model="attendance.date" :disabled='true'
                            />
                        </div>
                        <div class="col-md">
                            <x-ui-input
                                size="sm"
                                label="{{ translator('Subcontractor') }}"
                                model="workerInfo.subcontractor" :disabled='true'
                            />
                        </div>
                    @endif
                </div>
                @if ($selectWorker)
                    <div class="row mt-2">
                        <div class="col-md">
                            <x-ui-input
                                size="sm"
                                label="{{ translator('Subcontractor') }}"
                                model="workerInfo.subcontractor" :disabled='true'
                            />
                        </div>
                    </div>
                @endif
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
                <div class="row mt-2" wire:key="work-diary-{{ $attendance['date'] }}-{{ $isRange ? 'range' : 'day' }}">
                    <div class="col-md">
                        <x-ui-select
                            :options=$workDiaryOptionsItems
                            label="{{ translator('Workday diary') }}"
                            initOpt="{{ $isRange ? translator('Not available for a date range') : translator('w/o workday diary') }}"
                            size="sm"
                            model="attendance.working_day_record_id"
                            :disabled="$isRange"
                            title="{{ $isRange ? translator('Not available for a date range') : '' }}"
                        />
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md">
                        <x-ui.input
                            type="number"
                            label="{{ $isRange ? translator('Hours per day') : translator('Hours') }}"
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
                            @if ($isRange)
                                <th style="width: 110px">{{ translator('Date') }}</th>
                            @endif
                            <th style="width: 380px">{{ translator('Workday diary') }}</th>
                            <th class="text-center" style="width: 70px">{{ translator('Hours') }}</th>
                            <th style="width: 68px" class="text-end">{{ translator('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attCollection as $att)
                            <tr>
                                <td class="text-muted">{{ $att->getId() }}</td>
                                @if ($isRange)
                                    <td>{{ $att->getDate() }}</td>
                                @endif
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
                                <td colspan="{{ $isRange ? 5 : 4 }}" class="text-center">
                                    <i>
                                        @if ($selectWorker && empty($attendance['worker_id']))
                                            {{ translator('Select a worker to see the attendance!') }}
                                        @elseif ($isRange)
                                            {{ translator('No attendance in the selected range!') }}
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
