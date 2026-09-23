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
        <x-slot:headerActions>
            <div class="d-flex gap-2 align-items-center">
                <x-ui.modal.modal-open-btn
                    btn-type="war.sm"
                    icon="person-dash"
                    target-component="payroll-deduction"
                    :params="['month' => $selectedMonth, 'year' => $selectedYear]"
                />
                <x-ui.btn type="suc.sm" icon="file-earmark-spreadsheet" action="exportPayrollAction" />
                <x-v-divider style="height: 31px" />
                <x-ui.lock-btn :locked="$locked" type="dar.sm" action="lockingBtn" />
            </div>
        </x-slot:headerActions>
        <x-ui.card class="flex-fill d-flex flex-column" loading="selectedMonth, selectedYear">
            @if (empty($data))
                <div class="text-center text-muted py-5">
                    {{ translator('No payroll data for the selected period') }}
                </div>
            @else
                <div class="p-3 pt-0" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px; margin-bottom: 10px; overflow-y: auto; overflow-x: auto">
                    <table class="table table-striped">
                        <thead style="position: sticky; top: 0; background: linear-gradient(to bottom, #f8f8f8, #e6e6e6); box-shadow: 0 2px 0 0 #aaa; z-index: 1;">
                            <tr class="align-middle">
                                <th style="width: 250px">
                                    <x-ui.v2.input type="search" size="sm" :placeholder="translator('Worker')" model="search" event="live.debounce.300ms" />
                                </th>
                                <th style="text-align: center; border-left: 1px solid #ccc">{{ translator('Work hours') }}[h]</th>
                                <th style="text-align: center">{{ translator('PL') }}[d]</th>
                                <th style="text-align: center">{{ translator('SL') }}[d]</th>
                                <th style="text-align: center">{{ translator('HD') }}[d]</th>
                                <th style="text-align: center">{{ translator('Home') }}[d]</th>
                                <th style="text-align: center">{{ translator('Field') }}[d]</th>
                                <th style="text-align: center; border-left: 1px solid #ccc">{{ translator('Hourly rate') }}[€]</th>
                                <th style="text-align: center">{{ translator('Base') }}[€]</th>
                                <th style="text-align: center">{{ translator('Home') }}[€]</th>
                                <th style="text-align: center">{{ translator('Field') }}[€]</th>
                                <th style="text-align: center">{{ translator('Travel expense') }}[€]</th>
                                <th style="text-align: center">{{ translator('Phone expense') }}[€]</th>
                                <th style="text-align: center">{{ translator('Bonus') }}[€]</th>
                                <th style="text-align: center; border-left: 1px solid #ccc">{{ translator('Overall') }}[€]</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr class="align-middle">
                                    <td style="width: 250px">
                                        <div class="d-flex gap-2">
                                            <x-ui.employees.status-indicator empID="{{ $row['workerID'] }}" />
                                            <x-v-divider px=0 />
                                            <div class="">{{ str_pad($row['workerID'], 3, '0', STR_PAD_LEFT) }}</div>
                                            <x-v-divider px=0 />
                                            <div class="">{{ $row['name'] }}</div>
                                        </div>
                                    </td>
                                    <td style="text-align: center; width: 80px; border-left: 1px solid #ccc">{{ number_format(floatval($row['hours']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 50px">{{ number_format(floatval($row['paidLeaveDays']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 50px">{{ number_format(floatval($row['sickLeaveDays']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 50px;">{{ number_format(floatval($row['holidayDays']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 50px;">{{ number_format(floatval($row['homeDays']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 50px;">{{ number_format(floatval($row['fieldDays']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 100px; border-left: 1px solid #ccc">
                                        <x-ui.v2.input type="number" step="0.01" min="0" size="sm" :class="['text-center', 'no-spinner']" model="data.{{ $index }}.hourRate" :saved=$saved :disabled=$locked />
                                    </td>
                                    <td style="text-align: center; width: 100px">{{ number_format(floatval($row['base']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 80px;">{{ number_format(floatval($row['homeBonus']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 80px;">{{ number_format(floatval($row['fieldBonus']), 2, ',', '.') }}</td>
                                    <td style="text-align: center; width: 100px;">
                                        <x-ui.v2.input type="number" step="0.01" min="0" size="sm" :class="['text-center', 'no-spinner']" model="data.{{ $index }}.travelExpense" :saved=$saved :disabled=$locked />
                                    </td>
                                    <td style="text-align: center; width: 100px;">
                                        <x-ui.v2.input type="number" step="0.01" min="0" size="sm" :class="['text-center', 'no-spinner']" model="data.{{ $index }}.phoneExpense" :saved=$saved :disabled=$locked />
                                    </td>
                                    <td style="text-align: center; width: 100px;">
                                        <x-ui.v2.input type="number" step="0.01" min="0" size="sm" :class="['text-center', 'no-spinner']" model="data.{{ $index }}.bonus" :saved=$saved :disabled=$locked />
                                    </td>
                                    <td class="fw-bold" style="text-align: center; border-left: 1px solid #ccc">{{ number_format(floatval($row['net']), 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-4">{{ translator('No workers match the search') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </x-ui.card>
</div>
