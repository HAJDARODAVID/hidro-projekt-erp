<div class="px-2">
    
    <div class="row mt-1 px-2">
        <div class="col-md">
            <b>{{ translator('Basic info') }}</b>
            <hr class="m-0 p-0 my-2">
            <div class="row">
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('Name') }}" wModel="workerInfo.name"/></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('Surname') }}" wModel="workerInfo.surname"/></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('OIB') }}" wModel="workerInfo.oib"/></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('Phone nr') }}" wModel="workerInfo.phone"/></div>
                <div class="col-md-6"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('E-mail') }}" wModel="workerInfo.email"/></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui.input :disabled="!$canEdit" type="date" size="sm"  label="{{ translator('Date of employment') }}" wModel="workerInfo.doe"/></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" type="date" size="sm"  label="{{ translator('Contract expiration') }}" wModel="workerInfo.ced"/></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-8"><x-ui.select :disabled="!$canEdit" label="{{ translator('Workplace - contract') }}" :options=$workplaces class="form-select-sm"  /></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm"  label="{{ translator('Status') }}"/></div>
            </div>
            <hr>
            <b>{{ translator('Address') }}</b>
            <hr class="m-0 p-0 my-2">
            <div class="row">
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm" label="{{ translator('Street') }}" wModel="workerInfo.street"/></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm" label="{{ translator('Town') }}" wModel="workerInfo.town"/></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm" label="{{ translator('ZIP') }}" wModel="workerInfo.zip"/></div>
                <div class="col-md"><x-ui.input :disabled="!$canEdit" size="sm" label="{{ translator('County') }}" wModel="workerInfo.county"/></div>
            </div>
        </div>
        <div class="col-md-5">
            {{-- TODO: Napravi tu povijest radnika --}}
            {{-- <x-ui.card title="{{ translator('Worker history') }}"></x-ui.card> --}}
        </div>
    </div>
    <hr>
    <div class="mt-1 px-2">
        <b>{{ translator('Comment') }}</b>
        <textarea @disabled(!$canEdit) class="form-control no-border-radius" style="width: 100%" rows="5" wire:model.blur='workerInfo.comment'></textarea>
    </div>
    
</div>
