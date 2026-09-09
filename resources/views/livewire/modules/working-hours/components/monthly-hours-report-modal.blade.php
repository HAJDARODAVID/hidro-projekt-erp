<div>
    <x-ui.card :noBodyPadding=TRUE loading="" :border=FALSE>
        <div class="d-flex justify-content-between align-items-center p-1">
            <div class="text-muted small">
                {{ translator('Month') }}: {{ $month }} &nbsp; {{ translator('Year') }}: {{ $year }}
            </div>
        </div>
        <div class="d-flex justify-content-between p-1">
            <x-ui.input placeholder="{{ translator('Search') }}..." size="sm" :removeAddOnXP=TRUE wire:model.live.debounce.250ms='workerSearch'>
                @if ($workerSearch != NULL || $workerSearch != "")
                    <x-slot:append>
                        <x-ui.btn type="lig.sm" icon="trash" wClickMethod="resetArraySearchInput" />
                    </x-slot:append>
                @endif
            </x-ui.input>
            <x-ui.btn type="suc.sm" icon="file-earmark-spreadsheet"  wClickMethod="exportMonthlyHoursAction" />
        </div>
        <hr>
    </x-ui.card>
    @isset($data)
        <table class="table table-responsive">
            <thead>
                <tr>
                    <th></th>
                    <th style="text-align: center">{{ translator('Bonus') }}</th>
                    <th style="text-align: center">{{ translator('Work hours') }}</th>
                    <th style="text-align: center">{{ translator('Home') }}</th>
                    <th style="text-align: center">{{ translator('Field') }}</th>
                    <th style="text-align: center">{{ translator('PL') }}</th>
                    <th style="text-align: center">{{ translator('SL') }}</th>
                    <th style="text-align: center">{{ translator('HD') }}</th>
                    <th style="text-align: center">{{ translator('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $id => $worker)
                    @if ($worker['show-array-item-search'])
                        <tr>
                            <td>
                                <div class="d-flex gap-2">
                                    <x-ui.employees.status-indicator :empID=$id />
                                    <x-v-divider px=0 />
                                    <div class="">{{ str_pad($id, 3, '0', STR_PAD_LEFT) }}</div>
                                    <x-v-divider px=0 />
                                    <div class="">{{ $worker['name'] }}</div>
                                </div>
                            </td>
                            <td style="text-align: center">@if ($worker['bonus']) <i class="bi bi-check-lg text-success"></i> @else <i class="bi bi-x-lg text-danger"></i> @endif </td>
                            <td style="text-align: center">{{ $worker['work-hours'] }}</td>
                            <td style="text-align: center">{{ $worker['work-home'] }}</td>
                            <td style="text-align: center">{{ $worker['work-field'] }}</td>
                            <td style="text-align: center">{{ $worker['PL'] }}</td>
                            <td style="text-align: center">{{ $worker['SL'] }}</td>
                            <td style="text-align: center">{{ $worker['HD'] }}</td>
                            <td style="text-align: center">{{ $worker['work-hours-total'] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endisset
</div>
