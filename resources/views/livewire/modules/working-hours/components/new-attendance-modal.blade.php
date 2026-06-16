<div>
    <x-ui.modal.modal-open-btn :show=$displayIcon />

    <x-ui.modal :modalStatus=$modalStatus size="l">
        <x-slot:title>{{ strtoupper(translator('Add attendance entry')) }}</x-slot:title>
        <x-slot:footerRight>
            <x-ui.btn type="suc.sm" icon="floppy" />
        </x-slot:footerRight>

        <x-ui.card :noBodyPadding=TRUE loading="selectAbsenceReasonAction, resetHourInputAction" :border=FALSE>
            <div class="p-1" wire:key="container-{{ now() }}">
                <div class="row">
                    <div class="col-md-4">
                        <x-ui.input
                            type="date"
                            label="{{ translator('Date') }}"
                            class="form-control-sm"
                            wModel="attendance.date"
                        />
                    </div>

                    <div class="col-md">
                        <x-ui-select
                            :options=$workersOptionsItems
                            label="{{ translator('Worker') }}"
                            initOpt="{{ translator('Select worker') }}"
                            size="sm"
                            model="attendance.worker_id"
                            :disabled='$workersSelectAtt["disabled"] ?? false'
                        />
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md">
                        <x-ui.select
                            :options=$workDiaryOptions
                            label="{{ translator('Workday diary') }}"
                            initOption="{{ translator('Select work diary') }}"
                            class="form-select-sm"
                            wModel="attendance.working_day_record_id"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-ui.select
                            :options=$workDiaryOptions
                            label="{{ translator('Type') }}"
                            initOption="{{ translator('Select work diary') }}"
                            class="form-select-sm"
                            wModel="attendance.working_day_record_id"
                        />
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-1 justify-content-center" id='actions'>
                    <x-ui.employees.absence-btns :att='["size" => "sm"]'/>
                    <x-v-divider />
                    <div class="d-flex" style="width: 139px">
                        <x-ui.input
                            type="{{ $hourInputAtt['type'] ?? null }}"
                            class="form-control-sm"
                            wModel="hourInput"
                            :disabled='$hourInputAtt["disabled"]'
                            style="text-align: center;font-weight: bold; background-color: {{ $hourInputAtt['style']['background-color'] ?? null }}"
                        />
                        @if($hourInputAtt["delete"]) 
                            <x-ui.btn type="dan.sm" icon="trash" action='resetHourInputAction' />
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>
    </x-ui.modal>
</div>
