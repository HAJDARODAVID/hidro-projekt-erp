<div>
    <x-ui.card :extend=FALSE :border=FALSE :noBodyPadding=TRUE loading="saveBtn">
        <div class="alert alert-info py-2 mb-3">
            {{ translator('These amounts are global and apply to every unlocked payroll. Locked payrolls keep their saved values.') }}
        </div>

        {{-- Monthly bonus --}}
        <h6 class="mb-1">{{ translator('Monthly bonus') }}</h6>
        <div class="text-muted small mb-2">
            {{ translator('Paid once a month to workers eligible for the bonus, with no sick leave in the month.') }}
        </div>
        <div class="row g-2">
            <div class="col-md-4">
                <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Monthly bonus') }}[€]" model="monthlyBonus" :saved=$saved />
            </div>
        </div>

        <hr>

        {{-- Day bonuses --}}
        <h6 class="mb-1">{{ translator('Day bonuses') }}</h6>
        <div class="text-muted small mb-2">
            {{ translator('Paid per day worked, multiplied by the number of home / field days of the worker.') }}
        </div>
        <div class="row g-2">
            <div class="col-md-4">
                <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Home day bonus') }}[€/d]" model="homeDayBonus" :saved=$saved />
            </div>
            <div class="col-md-4">
                <x-ui-input type="number" step="0.01" min="0" label="{{ translator('Field day bonus') }}[€/d]" model="fieldDayBonus" :saved=$saved />
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3">
            <x-ui.btn type="suc" icon="check-lg" text="{{ translator('Save') }}" action="saveBtn" />
        </div>
    </x-ui.card>
</div>
