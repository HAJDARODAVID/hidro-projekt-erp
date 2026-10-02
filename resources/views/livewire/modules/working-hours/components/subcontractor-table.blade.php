<div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px;margin-bottom: 10px; overflow-y: auto; overflow-x: auto">
    @php
        $dates = $data->getDates();
        /**Default date for the modal opened from the subcontractor name: today in the current month, else the 1st of the shown month */
        $isCurrentMonth = $data->getMonth() == now()->format('n') && $data->getYear() == now()->format('Y');
        $defaultDate = $isCurrentMonth ? now()->format('Y-m-d') : sprintf('%04d-%02d-01', $data->getYear(), $data->getMonth());
    @endphp
    <table class="table table-responsive table-bordered">
        <thead style="border-bottom: 3px double #3f3f3f;">
            <tr>
                <th></th>
                <th style="text-align: center; width: 60px"><abbr title="{{ translator('Hours in month') }}">{{ translator('Hours') }}</abbr></th>
                <th style="text-align: center; width: 110px; border-right: 3px double #3f3f3f;"><abbr title="{{ translator('Cost in month') }}">{{ translator('Cost') }}</abbr></th>
                @foreach ($dates as $date)
                    <x-ui.tables.working-hours-report.th att="text:center.width:35px" day="{{ $date->format('N') }}">
                        {{ $date->format('d') }}
                    </x-ui.tables.working-hours-report.th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($data->getSubcontractors() as $subID => $subcontractor)
                {{-- Subcontractor row with the sums of all its workers --}}
                <tr style="background-color: #e9ecef;">
                    <td
                        style="font-weight: bold; cursor: pointer;"
                        title="{{ translator('Add attendance') }}"
                        data-trigger="global-modal"
                        data-component="subcontractor-attendance-info"
                        data-params="{{ json_encode(['subcontractor' => $subID, 'date' => $defaultDate]) }}"
                    >
                        <div class="d-flex gap-2">
                            <i class="bi bi-building"></i>
                            <div class="">{{ $subcontractor['name'] }}</div>
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: bold;">{{ $data->formatHours($data->subcontractorTotal($subID)) }}</td>
                    <td style="text-align: right; font-weight: bold; border-right: 3px double #3f3f3f !important;">{{ $data->formatCost($data->subcontractorCost($subID)) }}</td>
                    @foreach ($dates as $date)
                        <td style="text-align: center; font-weight: bold;">{{-- {{ $data->formatHours($data->subcontractorDayTotal($date, $subID)) }}--}}</td> 
                    @endforeach
                </tr>
                {{-- Workers of the subcontractor --}}
                @foreach ($data->getWorkers($subID) as $workerID => $worker)
                    <tr>
                        <td>
                            <div class="d-flex gap-2 ps-3">
                                <div class="">{{ str_pad($workerID, 3, '0', STR_PAD_LEFT) }}</div>
                                <x-v-divider px=0 />
                                <div class="">{{ $worker['name'] }}</div>
                            </div>
                        </td>
                        <td style="text-align: center;">{{ $data->formatHours($data->workerTotal($subID, $workerID)) }}</td>
                        <td style="text-align: right; border-right: 3px double #3f3f3f !important;">{{ $data->formatCost($data->workerCost($subID, $workerID)) }}</td>
                        @foreach ($dates as $date)
                            <x-ui.tables.working-hours-report.td
                                :date=$date
                                :attendance="$data->formatHours($data->hours($date, $subID, $workerID))"
                                component="subcontractor-attendance-info"
                                :missingStyle=FALSE
                                data-params="{{ json_encode(['worker' => $workerID, 'date' => $date->format('Y-m-d')]) }}"
                            />
                        @endforeach
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="{{ count($dates) + 3 }}" style="text-align: center;"><i>{{ translator('No subcontractor hours for the selected month!') }}</i></td>
                </tr>
            @endforelse
            {{-- Sum of all the subcontractors --}}
            {{--
            <tr style="border-top: 3px double #3f3f3f;">
                <td style="font-weight: bold;">{{ translator('Sum') }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $data->formatHours($data->total()) }}</td>
                <td style="text-align: right; font-weight: bold; border-right: 3px double #3f3f3f !important;">{{ $data->formatCost($data->totalCost()) }}</td>
                @foreach ($dates as $date)
                    <td style="text-align: center; font-weight: bold; @if ($date->format('N') > 5) background-color: #c9c9c9; @endif">{{ $data->formatHours($data->dayTotal($date)) }}</td>
                @endforeach
            </tr>
            --}}
        </tbody>
    </table>
</div>
