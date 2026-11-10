<div class="px-2">

    <div class="row mt-1 px-2">
        <div class="col-md">
            <b>{{ translator('Basic info') }}</b>
            <hr class="m-0 p-0 my-2">
            <div class="row">
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('Name') }}" model="workerInfo.name" :saved=$saved /></div>
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('Surname') }}" model="workerInfo.surname" :saved=$saved /></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('OIB') }}" model="workerInfo.oib" :saved=$saved inputmode="numeric" maxlength="11" /></div>
                <div class="col-md"><x-ui-input :disabled="!$canEdit" type="tel" size="sm" label="{{ translator('Phone nr') }}" model="workerInfo.phone" :saved=$saved /></div>
                <div class="col-md-6"><x-ui-input :disabled="!$canEdit" type="email" size="sm" label="{{ translator('E-mail') }}" model="workerInfo.email" :saved=$saved /></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui-input :disabled="!$canEdit" type="date" size="sm" label="{{ translator('Date of employment') }}" model="workerInfo.doe" event="change" :saved=$saved /></div>
                <div class="col-md"><x-ui-input :disabled="!$canEdit" type="date" size="sm" label="{{ translator('Contract expiration') }}" model="workerInfo.ced" event="change" :saved=$saved /></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-8"><x-ui-select :disabled="!$canEdit" size="sm" label="{{ translator('Workplace - contract') }}" :options=$workplaces model="workerInfo.workplace" :saved=$saved /></div>
                {{-- The status is changed with the worker status actions, here it is only shown --}}
                <div class="col-md"><x-ui-input :disabled=TRUE size="sm" label="{{ translator('Status') }}" model="workerInfo.status" /></div>
            </div>
            <hr>
            <b>{{ translator('Address') }}</b>
            <hr class="m-0 p-0 my-2">
            <div class="row">
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('Street') }}" model="workerInfo.street" :saved=$saved /></div>
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('Town') }}" model="workerInfo.town" :saved=$saved /></div>
            </div>
            <div class="row mt-2">
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('ZIP') }}" model="workerInfo.zip" :saved=$saved inputmode="numeric" maxlength="5" /></div>
                <div class="col-md"><x-ui-input :disabled="!$canEdit" size="sm" label="{{ translator('County') }}" model="workerInfo.county" :saved=$saved /></div>
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
        <textarea
            @disabled(!$canEdit)
            maxlength="255"
            class="form-control no-border-radius @if (($saved['workerInfo.comment'] ?? NULL) === TRUE) is-valid @elseif (($saved['workerInfo.comment'] ?? NULL) === FALSE) is-invalid @endif"
            @if (array_key_exists('workerInfo.comment', $saved)) data-flash-validation @endif
            style="width: 100%"
            rows="5"
            wire:model.blur='workerInfo.comment'></textarea>
    </div>

</div>
