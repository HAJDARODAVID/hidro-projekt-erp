<div class="p-3 pt-0" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px; margin-bottom: 10px; overflow-y: auto; overflow-x: hidden">

    <div class="d-flex gap-3 pb-2 mb-2" style="overflow-x: auto; border-bottom: 1px solid #e9ecef;">
        @foreach ($data->getDates() as $date)
            <div
                class="text-center flex-shrink-0"
                style="width: 32px; cursor: pointer;"
                wire:click="openDayAttendanceModal('{{ $date->format('U') }}')"
            >
                <div style="font-size: .65rem; color: #6c757d;">{{ mb_strtoupper($date->format('D')) }}</div>
                <div style="font-size: .9rem; font-weight: 600;">{{ $date->format('d') }}</div>
            </div>
        @endforeach
    </div>

    <x-ui.list-group>
        @foreach ($data->getWorkers() as $id => $worker)
            @php $summary = $data->monthlySummary($id); @endphp
            <li
                class="list-group-item"
                style="cursor: pointer"
                onclick="location.href='{{ route('getEmployeeWorkingHours', ['worker' => $id, 'month' => $data->getMonth(), 'year' => $data->getYear()]) }}';"
            >
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <x-ui.employees.status-indicator :empID=$id />
                        <div>
                            <div style="font-weight: 600;">{{ $worker['name'] }}</div>
                            <div style="font-size: .75rem; color: #6c757d;">#{{ str_pad($id, 3, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge text-bg-primary">{{ $summary['hours'] }}h</span>
                        @if ($summary['SL'] > 0)<span class="badge text-bg-warning">SL {{ $summary['SL'] }}</span>@endif
                        @if ($summary['PL'] > 0)<span class="badge text-bg-info">PL {{ $summary['PL'] }}</span>@endif
                        @if ($summary['HD'] > 0)<span class="badge text-bg-secondary">HD {{ $summary['HD'] }}</span>@endif
                        @if ($summary['ERR'] > 0)<span class="badge text-bg-danger">ERR {{ $summary['ERR'] }}</span>@endif
                        <i class="bi bi-chevron-right" style="color: #adb5bd;"></i>
                    </div>
                </div>
            </li>
        @endforeach
    </x-ui.list-group>

    @livewire('modules.working-hours.components.day-attendance-for-all-workers-modal')
</div>
