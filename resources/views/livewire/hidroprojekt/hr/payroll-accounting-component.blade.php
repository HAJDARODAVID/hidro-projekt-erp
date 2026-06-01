<div>
    <div class="d-flex justify-content-between align-items-center">
        <div class="row g-3">
            <div class="col" style="width: 200px">
                <label for="year" class="form-label"><b>Godina</b></label>
                <select id="year" class="form-select" wire:model.live='year'>
                    <option value="">Odaberi...</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    {{-- TODO: NAPRAVI TU DA JE DINAMIČNO --}}
                    <option value="2026">2026</option>
                </select>
            </div>
            <div class="col" style="width: 200px">
            <label for="month" class="form-label"><b>Mjesec</b></label>
            <select id="month" class="form-select" wire:model.live='month'>
                <option value="">Odaberi...</option>
                @foreach ($allMonths as $key => $month)
                    <option value="{{ $key }}">{{ $month }}</option>
                @endforeach
            </select>
            </div>
        </div>
        <div class="d-flex gap-1">
            <button class="btn btn-primary btn-lg d-flex align-items-center" wire:click='getPayrollAccountingData()'>
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <x-v-divider />
            <button class="btn btn-success btn-lg d-flex align-items-center" wire:click='savePayroll()' @if ($saved) disabled @endif>
                <i class="bi bi-floppy"></i>
            </button>
            @if ($canDelete && !$payroll->locked)
                <button class="btn btn-danger btn-lg d-flex align-items-center" wire:click='deletePayroll()'>
                    <i class="bi bi-trash"></i>
                </button>
            @endif
            @if ($payroll)
                <button class="btn btn-dark btn-lg d-flex align-items-center" wire:click='lockingBtn()'>
                    @if($payroll->locked)
                        <i class="bi bi-unlock-fill"></i>
                    @endif
                    @if(!$payroll->locked)
                        <i class="bi bi-lock-fill"></i>
                    @endif
                </button>
            @endif
            <x-v-divider />
            @livewire('hidroProjekt.hr.payroll.worker-deduction-modal', [
                'payroll' => $payroll,
                'locked' => $payroll ? $payroll->locked : FALSE,
            ])
            <x-v-divider />
            <button class="btn btn-success btn-lg d-flex align-items-center" wire:click='exportToExcel()'>
                <i class="bi bi-file-earmark-spreadsheet"></i>
            </button>
        </div>
    </div>
    <hr>

    @isset($data)
        <table class="table table-striped">
            <thead class="thead-light">
                <tr class="bg-secondary" style="border-bottom: 2px">
                    <th style="width: 160px">Ime i prezime</th>
                    <th style="width: 50px">OS<br>[Sati]</th>
                    <th style="width: 50px">GO<br>[Dani]</th>
                    <th style="width: 50px">BO<br>[Dani]</th>
                    <th style="width: 60px">Teren<br>[Dani]</th>
                    <th style="width: 60px;border-right: 1px solid">More<br>[Dani]</th>
                    <th style="width: 70px">Satnica</th>
                    <th style="width: 90px">Osnovnica<br>[€]</th>
                    <th style="width: 90px">Bonus<br>[{{ $bonus }}€]</th>
                    <th style="width: 90px">Teren<br>[Doma: {{ $fieldValues['home'] }}€]</th>
                    <th style="width: 100px">Teren<br>[More: {{ $fieldValues['field'] }}€]</th>
                    <th style="width: 80px">Putni<br>[€]</th>
                    <th style="width: 80px">Mobitel<br>[€]</th>
                    <th>Ukupno<br>[€]</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $worker_id => $worker)
                    <tr>
                        <td onclick="location.href='{{ route('hp_showWorker', ['id' => $worker_id, 'tab' => 2]) }}';">{{ $worker['fullName'] }}</td>
                        <td>{{ $worker['hours'] }}</td>
                        <td>{{ $worker['go'] }}</td>
                        <td>{{ $worker['bo'] }}</td>
                        <td>{{ $worker['field_1'] }}</td>
                        <td style="border-right: 1px solid">{{ $worker['field_2'] }}</td>
                        <td>
                            <div class="form-group">
                                <input type="number" class="form-control form-control-sm" wire:model.blur='data.{{ $worker_id }}.h_rate' @if ($data[$worker_id]['fix_rate'] || $locked) disabled @endif>
                            </div>
                        </td>
                        <td>{{ $worker['base'] }}</td>
                        <td>
                            <div class="form-group">
                                <input type="number" class="form-control form-control-sm" wire:model.blur='data.{{ $worker_id }}.bonus' @if ($locked) disabled @endif>
                            </div>
                        </td>

                        <td>{{ $worker['bonus_field_1'] }}</td>
                        <td>{{ $worker['bonus_field_2'] }}</td>
                        <td>
                            <div class="form-group">
                                <input type="number" class="form-control form-control-sm" wire:model.blur='data.{{ $worker_id }}.travel_exp' @if ($locked) disabled @endif>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <input type="number" class="form-control form-control-sm" wire:model.blur='data.{{ $worker_id }}.phone_exp' @if ($locked) disabled @endif>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <input type="text" class="form-control form-control-sm" style="font-weight: bold" wire:model.blur='data.{{ $worker_id }}.pay_out' disabled>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>  
    @endisset
    
    @livewire('hidroProjekt.hr.payroll.change-worker-h-rate-modal')
    <x-processing-modal target='getPayrollAccountingData, data, deletePayroll, savePayroll, lockingBtn'></x-processing-modal>
</div>

