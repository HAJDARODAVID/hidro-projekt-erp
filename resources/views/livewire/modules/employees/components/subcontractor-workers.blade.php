<div class="flex-fill position-relative">
    <style>
        /* Bold line between the head and the body. A shadow instead of a border, so it stays visible on the sticky head while scrolling */
        .subcontractor-workers-head th {
            padding-top: .6rem;
            padding-bottom: .6rem;
            border-bottom: 0;
            box-shadow: inset 0 -2px 0 #adb5bd;
        }
    </style>
    @if (empty($workers) && $workerSearch === NULL)
        <div class="text-center text-muted py-3"><i>{{ translator('This subcontractor has no active workers') }}</i></div>
    @else
        {{-- Fills the rest of the card, scrolls when there are more workers than fit --}}
        <div class="col-md-6" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; overflow-y: auto">
            <table class="table table-sm table-striped align-middle">
                <thead class="subcontractor-workers-head" style="position: sticky; top: 0; background: #fff; z-index: 1;">
                    <tr>
                        <th style="width: 80px">{{ translator('ID') }}</th>
                        <th>
                            <x-ui.input size="sm" placeholder="{{ translator('Search worker') }}" wModel="workerSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                                @if ($workerSearch !== NULL)
                                    <x-slot:append>
                                        <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetWorkerSearchInput" />
                                    </x-slot:append>
                                @endif
                            </x-ui.input>
                        </th>
                        <th style="width: 150px; text-align: center">{{ translator('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workers as $worker)
                        @php $isActive = $worker['status'] == \App\Models\Employees\SubcontractorWorker::STATUS_ACTIVE; @endphp
                        <tr wire:key="subcontractor-worker-{{ $worker['id'] }}" @class(['text-muted' => !$isActive])>
                            <td>{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $worker['firstName'] }} {{ $worker['lastName'] }}</td>
                            <td>
                                <div class="form-check form-switch m-0 d-flex justify-content-center" wire:key="subcontractor-worker-status-{{ $worker['id'] }}-{{ $worker['status'] }}">
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        class="form-check-input subcontractor-status-switch"
                                        title="{{ translator($statuses[$worker['status']] ?? '-') }}"
                                        @checked($isActive)
                                        wire:click="toggleWorkerStatus({{ $worker['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleWorkerStatus"
                                    >
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3"><i>{{ translator('No workers found') }}</i></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
