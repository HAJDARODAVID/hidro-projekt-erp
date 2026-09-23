<div>
    @if ($locked)
        <div class="alert alert-warning py-2 mb-3">
            {{ translator('The payroll of this period is locked, no new deductions can be added.') }}
        </div>
    @endif

    <div class="row g-2 align-items-end">
        <div class="col-12">
            <x-ui.select
                :options="$workers"
                :initOption="translator('Select worker')"
                label="{{ translator('Worker') }}"
                wModel="selectedWorkerID"
                :disabled="$presetWorkerID !== NULL || $locked"
            />
        </div>
        <div class="col-6">
            <x-ui.v2.input type="number" step="0.01" min="0.01" label="{{ translator('Amount') }}[€]" model="amount" :disabled="$locked" />
        </div>
        <div class="col-6 d-flex justify-content-end">
            <x-ui.btn type="suc" text="{{ translator('Add deduction') }}" action="confirmBtn" :disabled="$locked" />
        </div>
        <div class="col-12">
            <div class="form-group">
                <label>{{ translator('Reason') }}</label>
                <textarea class="form-control no-border-radius" rows="2" maxlength="255" wire:model="reason" placeholder="{{ translator('Reason') }}" @if ($locked) disabled @endif></textarea>
            </div>
        </div>
    </div>

    <hr>

    <div style="max-height: 40vh; overflow-y: auto;">
        <table class="table table-sm">
            <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                <tr>
                    @if (!$presetWorkerID)
                        <th>{{ translator('Worker') }}</th>
                    @endif
                    <th style="text-align: center">{{ translator('Amount') }}[€]</th>
                    <th>{{ translator('Reason') }}</th>
                    @if (!$locked)
                        <th style="width: 50px"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($deductions as $deduction)
                    <tr>
                        @if (!$presetWorkerID)
                            <td>{{ $deduction['workerLabel'] }}</td>
                        @endif
                        <td style="text-align: center">{{ number_format((float) $deduction['amount'], 2, ',', '.') }}</td>
                        <td>{{ $deduction['reason'] }}</td>
                        @if (!$locked)
                            <td style="text-align: center">
                                <x-ui.btn type="dan.sm" icon="trash" action="deleteDeductionAction" param="{{ $deduction['id'] }}" wire:confirm="{{ translator('Delete this deduction?') }}" />
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($presetWorkerID ? 2 : 3) + ($locked ? 0 : 1) }}" class="text-center text-muted py-3">
                            {{ translator('No deductions for this payroll') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
