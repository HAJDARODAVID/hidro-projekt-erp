<div>
    <x-ui.card :extend=FALSE :border=FALSE :noBodyPadding=TRUE loading="saveBtn, resetToDefaultBtn">
        <div class="alert alert-info py-2 mb-3">
            {{ translator('These rules are global and apply to every unlocked payroll. Locked payrolls keep their saved values.') }}
        </div>

        {{-- Base --}}
        <h6 class="mb-1">{{ translator('Base') }}</h6>
        <div class="text-muted small mb-2">
            {{ translator('Base = fixed rate (when enabled and set on the worker), otherwise hours × hourly rate.') }}
        </div>

        <div class="d-flex gap-4 flex-wrap align-items-end">
            <div>
                <label>{{ translator('Hours counted in the base') }}</label>
                <div class="d-flex gap-3 flex-wrap pt-1">
                    @foreach ($hourSources as $source => $label)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="payrollCalcHours-{{ $source }}" value="{{ $source }}" wire:model="baseHours">
                            <label class="form-check-label" for="payrollCalcHours-{{ $source }}">{{ translator($label) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="width: 180px">
                <x-ui-input type="number" step="0.5" min="0.5" max="24" label="{{ translator('Hours per leave day') }}" model="hoursPerDay" />
            </div>
        </div>

        <div class="form-check form-switch pt-3">
            <input type="checkbox" role="switch" class="form-check-input" id="payrollCalcUseFixRate" wire:model="useFixRate">
            <label class="form-check-label" for="payrollCalcUseFixRate">{{ translator('Use the fixed rate instead of hours × hourly rate when the worker has one') }}</label>
        </div>

        <hr>

        {{-- Net --}}
        <h6 class="mb-1">{{ translator('Net') }}</h6>
        <div class="text-muted small mb-2">
            {{ translator('Gross = sum of the added components. Net = gross − sum of the subtracted components.') }}
        </div>

        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ translator('Component') }}</th>
                    @foreach ($operations as $label)
                        <th style="text-align: center; width: 100px">{{ translator($label) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($components as $component => $label)
                    <tr wire:key="payroll-calc-component-{{ $component }}">
                        <td>{{ translator($label) }}</td>
                        @foreach ($operations as $operation => $operationLabel)
                            <td style="text-align: center">
                                <input
                                    type="radio"
                                    class="form-check-input"
                                    name="payrollCalcComponent-{{ $component }}"
                                    value="{{ $operation }}"
                                    wire:model="netComponents.{{ $component }}"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-end gap-2 pt-3">
            <x-ui.btn type="sec" icon="arrow-counterclockwise" text="{{ translator('Default') }}" action="resetToDefaultBtn" />
            <x-ui.btn type="suc" icon="check-lg" text="{{ translator('Save') }}" action="saveBtn" />
        </div>
    </x-ui.card>
</div>
