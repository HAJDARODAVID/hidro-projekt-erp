@php
    $dates = $data->getDates();
    /**Default date for the modal opened from the subcontractor name: today in the current month, else the 1st of the shown month */
    $isCurrentMonth = $data->getMonth() == now()->format('n') && $data->getYear() == now()->format('Y');
    $defaultDate = $isCurrentMonth ? now()->format('Y-m-d') : sprintf('%04d-%02d-01', $data->getYear(), $data->getMonth());
    $subIDs = array_map('strval', array_keys($data->getSubcontractors()));
@endphp
{{--
    The workers are collapsed into their subcontractor; a click on the arrow (or the sums) opens them.
    Which subcontractors are open is kept in the browser (localStorage), because this table is rendered
    new on every refresh (month change, saved attendance). Shared with the mobile table.
--}}
<div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px;margin-bottom: 10px; overflow-y: auto; overflow-x: auto"
    x-data="@include('livewire.modules.working-hours.components.partials.subcontractor-collapse-data')">
    <style>[x-cloak] { display: none !important; }</style>
    <table class="table table-responsive table-bordered">
        <thead style="border-bottom: 3px double #3f3f3f;">
            <tr>
                <th class="align-middle">
                    @if (!empty($subIDs))
                        <button type="button" class="btn btn-sm btn-light gray-border-color no-border-radius py-0 px-1" @click="toggleAll()"
                            :title="allOpen() ? @js(translator('Collapse all')) : @js(translator('Expand all'))">
                            <i class="bi" :class="allOpen() ? 'bi-chevron-double-up' : 'bi-chevron-double-down'"></i>
                        </button>
                    @endif
                </th>
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
                @php $workerCount = count($data->getWorkers($subID)); @endphp
                <tr style="background-color: #e9ecef;" wire:key="sub-row-{{ $subID }}">
                    <td style="font-weight: bold;">
                        <div class="d-flex gap-2 align-items-center">
                            {{-- Open / close the workers (.stop: the click must not open the attendance modal) --}}
                            <button type="button" class="btn btn-sm btn-light gray-border-color no-border-radius py-0 px-1" @click.stop="toggle(@js((string) $subID))"
                                :title="isOpen(@js((string) $subID)) ? @js(translator('Hide the workers')) : @js(translator('Show the workers'))">
                                <i class="bi" :class="isOpen(@js((string) $subID)) ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                            </button>
                            <div class="d-flex gap-2 align-items-center" style="cursor: pointer;"
                                title="{{ translator('Add attendance') }}"
                                data-trigger="global-modal"
                                data-component="subcontractor-attendance-info"
                                data-params="{{ json_encode(['subcontractor' => $subID, 'date' => $defaultDate]) }}">
                                <i class="bi bi-building"></i>
                                <div class="text-nowrap">{{ $subcontractor['name'] }}</div>
                            </div>
                            <span class="badge bg-secondary no-border-radius fw-normal" title="{{ translator('Workers') }}">{{ $workerCount }}</span>
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: bold; cursor: pointer;" @click="toggle(@js((string) $subID))">{{ $data->formatHours($data->subcontractorTotal($subID)) }}</td>
                    <td style="text-align: right; font-weight: bold; cursor: pointer; border-right: 3px double #3f3f3f !important;" @click="toggle(@js((string) $subID))">{{ $data->formatCost($data->subcontractorCost($subID)) }}</td>
                    @foreach ($dates as $date)
                        <td style="text-align: center; font-weight: bold;">{{-- {{ $data->formatHours($data->subcontractorDayTotal($date, $subID)) }}--}}</td> 
                    @endforeach
                </tr>
                {{-- Workers of the subcontractor --}}
                @foreach ($data->getWorkers($subID) as $workerID => $worker)
                    <tr x-show="isOpen(@js((string) $subID))" x-cloak wire:key="sub-worker-{{ $subID }}-{{ $workerID }}">
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
