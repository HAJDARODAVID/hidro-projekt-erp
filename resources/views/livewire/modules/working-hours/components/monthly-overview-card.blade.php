<x-ui.card :noBodyPadding=TRUE class="h-100 d-flex flex-column">
    <x-slot:title>
        <div class="d-flex gap-2 align-items-center">
            <div class="">{{ translator('Month') }}:</div>
            <x-v-divider style="height: 31px"/>
            <x-ui.select :options=$months class="form-select-sm" wModel='selectedMonth' style="width: 100px" />
            <x-v-divider px=0 style="height: 31px"/>
            <x-ui.select :options=$years class="form-select-sm"  wModel='selectedYear' style="width: 100px" />
        </div>
    </x-slot:title>
    <x-slot:headerActions>
        <div class="d-flex gap-2 align-items-center">
            <x-ui.btn type="suc.sm" icon="file-earmark-spreadsheet" />
        </div>
    </x-slot:headerActions>
    <x-ui.card class="flex-fill d-flex flex-column" loading="selectedMonth, selectedYear, refreshMe, deleteAttendanceAction, attendance">
        
        <div class="p-3 pt-0" style="position: absolute;top: 0; right: 0; bottom: 0; left: 0; margin-top: 10px;margin-bottom: 10px; overflow-y: auto">
            @isset($attendance['report'])
            <table class="table table-sm table-striped" style="text-align: center">
                    <tbody>
                        <tr class="table-header">
                            <th class="table-border-sides">WORK HOURS[h]</th>
                            <th class="table-border-sides">TOTAL HOURS[h]</th>
                            <th class="table-border-sides">HOME[d]</th>
                            <th class="table-border-sides">FIELD[d]</th>
                            <th class="table-border-sides">SL[d]</th>
                            <th class="table-border-sides">PL[d]</th>
                            <th class="table-border-sides">HD[d]</th>
                            <th class="table-border-sides">BONUS</th>
                        </tr>
                        <tr>
                            <td class="table-border-sides">{{ $attendance['report']['work-hours'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['work-hours-total'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['work-home'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['work-field'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['SL'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['PL'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['HD'] }}</td>
                            <td class="table-border-sides">{{ $attendance['report']['bonus'] }}</td>
                        </tr>
                    </tbody>
                </table>
                <hr>
            @endisset
            @isset($attendance['per-day'])
                <table class="table table-sm table-striped">
            
                    <thead class="table-header">
                        
                        <tr style="vertical-align: middle;">
                            <th style="width:10px" class="table-border-sides">#</th>
                            <th class="table-border-sides" style="width:95px; text-align: center">{{ mb_strtoupper(translator("date")) }}</th>
                            <th  class="table-border-sides"style="width:100px; text-align: center">#{{ mb_strtoupper(translator("wdr")) }}</th>
                            <th class="table-border-sides">{{ mb_strtoupper(translator("construction site")) }}</th>
                            <th class="table-border-sides" style="width:100px; text-align: center">{{ mb_strtoupper(translator("work hours")) }}</th>
                            <th class="table-border-sides" style="width:100px; text-align: center">{{ mb_strtoupper(translator("type")) }}</th>
                            <th class="table-border-sides" style="width:50px; text-align: center">{{ mb_strtoupper(translator("sl")) }}</th>
                            <th class="table-border-sides" style="width:50px; text-align: center">{{ mb_strtoupper(translator("pl")) }}</th>
                            <th class="table-border-sides" style="width:50px; text-align: center">{{ mb_strtoupper(translator("hd")) }}</th>
                            <th class="table-border-sides" style="width:10px; text-align: center"><x-ui.btn type="pri.sm" icon="plus-square" wClickMethod="openNewAttendanceModalAction" /></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendance['per-day'] as $attID => $att)
                            <tr>
                                <td class="table-border-sides" style="" wire:key="attendance-row-{{ $attID }}">{{ $att['id'] }}</td>
                                <td class="table-border-sides" style="padding: 4px 10px;text-align: center " wire:key="attendance-row-{{ $attID }}">{{ $att['date'] }}</td>
                                <td class="table-border-sides" style="padding: 4px 10px;text-align: center " wire:key="attendance-row-{{ $attID }}">
                                    <div class="d-flex justify-content-center gap-1">
                                        {{ $att['wdr'] }}
                                        @if ($att['wdr'])
                                            <x-v-divider />
                                            <i class="bi bi-pencil bg-primary-subtle" style="cursor: pointer;padding: 0px 4px"
                                            wire:click="openEditDiaryModalAction('{{ $attID }}')"></i>
                                        @else
                                            <i class="bi bi-plus-square" style="cursor: pointer;padding: 0px 4px" wire:click="openEditDiaryModalAction('{{ $attID }}')"></i>
                                        @endif
                                        
                                    </div>
                                </td>
                                <td class="table-border-sides" wire:key="attendance-row-{{ $attID }}">{{ $att['cs_name'] }}</td>
                                <td class="table-border-sides px-2" style="text-align: center;" wire:key="attendance-row-{{ $attID }}">
                                    <x-ui.input 
                                        type="number" 
                                        size="sm"
                                        style="text-align: center" 
                                        class="{{ isset($saved['attendance.per-day.'.$attID.'.hours']) ?  'is-valid' : NULL }}"
                                        wModel="attendance.per-day.{{ $attID }}.hours" />
                                </td>
                                <td class="table-border-sides px-2" style="text-align: center;" wire:key="attendance-row-{{ $attID }}">
                                    <x-ui.select 
                                    :options=$attTypes 
                                    class="form-select-sm {{ isset($saved['attendance.per-day.'.$attID.'.type']) ?  'is-valid' : NULL }}" 
                                    wModel="attendance.per-day.{{ $attID }}.type" />
                                </td>
                                <td class="table-border-sides" style="text-align: center;" wire:key="attendance-row-{{ $attID }}">
                                    <input class="form-check-input" type="checkbox" wire:model.change="attendance.per-day.{{ $attID }}.abs-sl">
                                </td>
                                <td class="table-border-sides" style="text-align: center;" wire:key="attendance-row-{{ $attID }}">
                                    <input class="form-check-input" type="checkbox" wire:model.change="attendance.per-day.{{ $attID }}.abs-pl">
                                </td>
                                <td class="table-border-sides" style="text-align: center;" wire:key="attendance-row-{{ $attID }}">
                                    <input class="form-check-input" type="checkbox" wire:model.change="attendance.per-day.{{ $attID }}.abs-hd">
                                </td>
                                <td class="table-border-sides" wire:key="attendance-row-{{ $attID }}">
                                    <div class="d-flex gap-2 px-2">
                                        <x-ui.btn type="dan.sm" icon="trash" wClickMethod="deleteAttendanceAction" wClickParam="{{ $attID }}" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endisset
        
        </div>
    </x-ui.card>
    @livewire('modules.working-hours.components.edit-work-diary-on-attendance-modal', ['displayIcon'=>FALSE])
    @livewire('modules.working-hours.components.new-attendance-modal', ['displayIcon'=>FALSE])
</x-ui.card>
