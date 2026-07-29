<div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px;margin-bottom: 10px; overflow-y: auto; overflow-x: auto">
    <table class="table table-responsive table-bordered">
        <thead style="border-bottom: 3px double #3f3f3f;">
            <tr>
                <th style="border-right: 3px double #3f3f3f;"></th>
                @foreach ($data->getDates() as $date)
                    <x-ui.tables.working-hours-report.th att="text:center.width:35px" day="{{ $date->format('N') }}" lwAction="openDayAttendanceModal" lwActionAtt="{{ $date->format('U') }}">
                        {{ $date->format('d') }}
                    </x-ui.tables.working-hours-report.th>
                @endforeach
                
            </tr>
        </thead>
        <tbody>
            @foreach ($data->getWorkers() as $id => $worker)
                <tr>
                    <td onclick="location.href='{{ route('getEmployeeWorkingHours', ['worker'=> $id,'month' => $data->getMonth(),'year'=>$data->getYear()]) }}';" style="cursor: pointer">
                        <div class="d-flex gap-2">
                            <x-ui.employees.status-indicator :empID=$id />
                            <x-v-divider px=0 />
                            <div class="">{{ str_pad($id, 3, '0', STR_PAD_LEFT) }}</div>
                            <x-v-divider px=0 />
                            <div class="">{{ $worker['name'] }}</div>
                        </div>
                    </td>
                    @foreach ($data->getDates() as $date)
                        @if ($loop->first)
                            <x-ui.tables.working-hours-report.td 
                                :date=$date 
                                attendance="{{ $data->date($date)->worker($id)->attendance() }}" 
                                att="border:left-03-double-#3f3f3f"
                            />
                        @else
                            <x-ui.tables.working-hours-report.td
                                :date=$date
                                attendance="{{ $data->date($date)->worker($id)->attendance() }}"
                                data-params="{{ json_encode(['worker' => $id, 'date' => $date->format('Y-m-d')]) }}"
                            />
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @livewire('modules.working-hours.components.day-attendance-for-all-workers-modal')
    
    <x-ui.please-wait loading="openDayAttendanceModal, openModal"/>
</div>
