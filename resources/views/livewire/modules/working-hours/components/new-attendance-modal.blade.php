<div>
    <x-ui.modal.modal-open-btn :show=$displayIcon />

    <x-ui.modal :modalStatus=$modalStatus size="l">
        <x-slot:title>{{ strtoupper(translator('Add attendance entry')) }}</x-slot:title>

        <x-ui.card :noBodyPadding=TRUE loading="" :border=FALSE>
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
                        <x-ui.select
                            :options=$workDiaryOptions
                            label="{{ translator('Worker') }}"
                            initOption="{{ translator('Select worker') }}"
                            class="form-select-sm"
                            wModel="attendance.workDiary"
                        />
                    </div>

                    {{-- <div class="col-md-4">
                        <x-ui.input
                            type="number"
                            label="{{ translator('Hours') }}"
                            class="form-control-sm"
                            wModel="attendance.hours"
                            min="0"
                            step="0.25"
                        />
                    </div> --}}
                </div>
                <div class="row mt-3">
                    <div class="col-md">
                        <x-ui.select
                            :options=$workDiaryOptions
                            label="{{ translator('Workday diary') }}"
                            initOption="{{ translator('Select worker') }}"
                            class="form-select-sm"
                            wModel="attendance.workDiary"
                        />
                    </div>
                </div>
                <hr>
            </div>
        </x-ui.card>
    </x-ui.modal>
</div>
