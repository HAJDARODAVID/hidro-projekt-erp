<div>
    <x-ui.card :noBodyPadding=TRUE :border=FALSE>
        <div class="d-flex justify-content-between align-items-center p-1">
            <div class="d-flex gap-2 align-items-center">
                {{ translator('Month') }} / {{ translator('Year') }}
                <x-v-divider px=0 style="height: 31px"/>
                <x-ui.select :options=$months class="form-select-sm" wModel='selectedMonth' style="width: 100px" />
                <x-v-divider px=0 style="height: 31px"/>
                <x-ui.select :options=$years class="form-select-sm"  wModel='selectedYear' style="width: 100px" />
            </div>
            <x-ui.btn type="suc.sm" icon="file-earmark-spreadsheet" action="exportAllAction" :disabled="$total['hours'] == 0" title="{{ translator('Export all subcontractors in one file') }}">
                {{ translator('Export all') }}
            </x-ui.btn>
        </div>
        <hr>
        <div style="max-height: 60vh; overflow-y: auto; overflow-x: auto;" wire:loading.class="blurred-content" wire:target="selectedMonth, selectedYear">
            <table class="table">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                    <tr>
                        <th>{{ translator('Subcontractor') }}</th>
                        <th style="text-align: center">{{ translator('Workers') }}</th>
                        <th style="text-align: center">{{ translator('Work hours') }}</th>
                        <th style="text-align: right">{{ translator('Cost') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subcontractors as $id => $subcontractor)
                        <tr @if ($subcontractor['hours'] == 0) class="text-secondary" @endif>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <i class="bi bi-building"></i>
                                    <div class="">{{ $subcontractor['name'] }}</div>
                                </div>
                            </td>
                            <td style="text-align: center">{{ $subcontractor['workers'] }}</td>
                            <td style="text-align: center">{{ $subcontractor['hours'] + 0 }}</td>
                            <td style="text-align: right">{{ $subcontractor['cost'] }}</td>
                            <td style="text-align: right">
                                <x-ui.btn type="suc.sm" icon="file-earmark-spreadsheet" action="exportSubcontractorAction" param="{{ $id }}" :disabled="$subcontractor['hours'] == 0" title="{{ translator('Export') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center"><i>{{ translator('No subcontractors found!') }}</i></td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($subcontractors) > 0)
                    <tfoot>
                        <tr style="border-top: 3px double #3f3f3f;">
                            <td style="font-weight: bold;">{{ translator('Sum') }}</td>
                            <td></td>
                            <td style="text-align: center; font-weight: bold;">{{ $total['hours'] + 0 }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ $total['cost'] }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-ui.card>

    <x-ui.please-wait loading="exportSubcontractorAction, exportAllAction"/>
</div>
