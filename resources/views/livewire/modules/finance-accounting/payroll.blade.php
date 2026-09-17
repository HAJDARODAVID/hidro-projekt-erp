<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <x-slot:title>
            <div class="d-flex gap-2 align-items-center">
                <div class="">{{ translator('Month') }} / {{ translator('Year') }}</div>
                <x-v-divider style="height: 31px"/>
                <x-ui.select :options=$months class="form-select-sm" wModel='selectedMonth' style="width: 100px" />
                <x-v-divider px=0 style="height: 31px"/>
                <x-ui.select :options=$years class="form-select-sm" wModel='selectedYear' style="width: 100px" />
            </div>
        </x-slot:title>
        <x-ui.card class="flex-fill d-flex flex-column" loading="selectedMonth, selectedYear">
            @if (empty($data))
                <div class="text-center text-muted py-5">
                    {{ translator('No payroll data for the selected period') }}
                </div>
            @else
                <div class="tableFixHead">
                    <table class="table table-responsive table-sm table-striped">
                        <thead>
                            <tr>
                                <th>{{ translator('Worker') }}</th>
                                <th style="text-align: center">{{ translator('Work hours') }}[h]</th>
                                <th style="text-align: center">{{ translator('Hourly rate') }}[€]</th>
                                <th style="text-align: center">{{ translator('Gross') }}[€]</th>
                                <th style="text-align: center">{{ translator('Deductions') }}[€]</th>
                                <th style="text-align: center">{{ translator('Net') }}[€]</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $row)
                                <tr>
                                    <td>{{ $row['worker'] }}</td>
                                    <td style="text-align: center">{{ number_format(floatval($row['hours']), 2, ',', '.') }}</td>
                                    <td style="text-align: center">{{ number_format(floatval($row['rate']), 2, ',', '.') }}</td>
                                    <td style="text-align: center">{{ number_format(floatval($row['gross']), 2, ',', '.') }}</td>
                                    <td style="text-align: center">{{ number_format(floatval($row['deductions']), 2, ',', '.') }}</td>
                                    <td style="text-align: center">{{ number_format(floatval($row['net']), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </x-ui.card>
</div>
