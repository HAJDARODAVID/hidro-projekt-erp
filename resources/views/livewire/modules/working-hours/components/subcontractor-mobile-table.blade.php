<div class="p-3 pt-0" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px; margin-bottom: 10px; overflow-y: auto; overflow-x: hidden">

    @forelse ($data->getSubcontractors() as $subID => $subcontractor)
        <div class="d-flex justify-content-between align-items-center gap-2 py-2 px-1" style="border-bottom: 1px solid #e9ecef;">
            <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                <i class="bi bi-building"></i>
                <div class="text-truncate" style="font-weight: 700;">{{ $subcontractor['name'] }}</div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                <span class="badge text-bg-primary">{{ $data->formatHours($data->subcontractorTotal($subID)) }}h</span>
                <span class="badge text-bg-secondary">{{ $data->formatCost($data->subcontractorCost($subID)) }}</span>
            </div>
        </div>
        <x-ui.list-group class="mb-3">
            @foreach ($data->getWorkers($subID) as $workerID => $worker)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <div class="text-truncate" style="min-width: 0;">
                            <div class="text-truncate" style="font-weight: 600;">{{ $worker['name'] }}</div>
                            <div style="font-size: .75rem; color: #6c757d;">#{{ str_pad($workerID, 3, '0', STR_PAD_LEFT) }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <span class="badge text-bg-primary">{{ $data->formatHours($data->workerTotal($subID, $workerID)) }}h</span>
                            <span class="badge text-bg-light">{{ $data->formatCost($data->workerCost($subID, $workerID)) }}</span>
                        </div>
                    </div>
                </li>
            @endforeach
        </x-ui.list-group>
    @empty
        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No subcontractor hours for the selected month!') }}</i></div></div>
    @endforelse

    @if (count($data->getSubcontractors()) > 0)
        <div class="d-flex justify-content-between align-items-center gap-2 py-2 px-1" style="border-top: 3px double #3f3f3f;">
            <div style="font-weight: 700;">{{ translator('Sum') }}</div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                <span class="badge text-bg-primary">{{ $data->formatHours($data->total()) }}h</span>
                <span class="badge text-bg-secondary">{{ $data->formatCost($data->totalCost()) }}</span>
            </div>
        </div>
    @endif
</div>
