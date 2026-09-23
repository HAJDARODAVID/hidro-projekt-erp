<div>
    <div class="row g-2">
        <div class="col-md-3">
            <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Hourly rate') }}[€]" model="hourRate" :saved=$saved />
        </div>
        <div class="col-md-3">
            <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Fixed rate') }}[€]" model="fixRate" :saved=$saved />
        </div>
        <div class="col-md-3">
            <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Travel expense') }}[€]" model="travelExpense" :saved=$saved />
        </div>
        <div class="col-md-3">
            <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Phone expense') }}[€]" model="phoneExpense" :saved=$saved />
        </div>
    </div>

    <div class="form-check form-switch pt-3">
        <input
            type="checkbox"
            role="switch"
            id="workerPayrollInfoBonus"
            class="form-check-input @if (($saved['bonus'] ?? NULL) === TRUE) is-valid @elseif (($saved['bonus'] ?? NULL) === FALSE) is-invalid @endif"
            wire:model.live="bonus"
            @if (array_key_exists('bonus', $saved)) data-flash-validation @endif
        >
        <label class="form-check-label" for="workerPayrollInfoBonus">{{ translator('Eligible for the monthly bonus') }}</label>
    </div>
</div>
