<?php

namespace App\Livewire\Modules\FinanceAccounting\Components;

use App\Exceptions\ErrorMessage;
use App\Livewire\LivewireController;
use App\Livewire\Modules\FinanceAccounting\Payroll;
use App\Models\Payroll\Deduction;
use App\Models\Payroll\Payroll as PayrollModel;
use App\Services\Payroll\GetPayrollService;

class PayrollDeductionModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    public $selectedMonth = NULL;
    public $selectedYear = NULL;

    /**Worker ID passed in as a param. When set, the worker select is locked to it. */
    public $presetWorkerID = NULL;

    /**Selected worker in the form */
    public $selectedWorkerID = NULL;

    public $amount = NULL;
    public $reason = NULL;

    /**Whether the payroll of the selected period is locked (no new deductions allowed) */
    public $locked = FALSE;

    /**[workerID => label] options for the worker select, limited to the workers of this payroll */
    public $workers = [];

    /**Deduction rows to display in the table below the form */
    public $deductions = [];

    public function mount()
    {
        $this->selectedMonth = $this->params['month'] ?? date('n');
        $this->selectedYear = $this->params['year'] ?? date('Y');
        $this->presetWorkerID = isset($this->params['workerID']) ? (int) $this->params['workerID'] : NULL;
        $this->selectedWorkerID = $this->presetWorkerID;

        $this->loadPayrollData();
    }

    /**
     * Add a deduction for the selected worker on this payroll, then refresh the
     * deduction list here and the payroll table behind the modal (its net pay changes).
     *
     * @return void
     */
    public function confirmBtn()
    {
        try {
            if ($this->locked) throw new ErrorMessage(translator('The payroll of this period is locked.'));
            if (!isset($this->workers[$this->selectedWorkerID])) throw new ErrorMessage(translator('Select a worker.'));
            if (!is_numeric($this->amount) || (float) $this->amount <= 0) throw new ErrorMessage(translator('Enter a valid amount.'));

            $payroll = $this->getPayroll();
            if ($payroll === NULL) throw new ErrorMessage(translator('Payroll not found for the selected period.'));

            Deduction::create([
                'payroll_id' => $payroll->id,
                'worker_id'  => (int) $this->selectedWorkerID,
                'amount'     => (float) $this->amount,
                'reason'     => $this->reason,
            ]);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->amount = NULL;
        $this->reason = NULL;
        $this->loadPayrollData();
        $this->dispatch('refresh-payroll-data')->to(Payroll::class);
        $this->notifyMe(translator('Deduction added.'));
    }

    /**
     * Remove a deduction from this payroll, then refresh the deduction list here
     * and the payroll table behind the modal (its net pay changes).
     *
     * @param int $id
     * @return void
     */
    public function deleteDeductionAction($id)
    {
        try {
            if ($this->locked) throw new ErrorMessage(translator('The payroll of this period is locked.'));

            $payroll = $this->getPayroll();
            if ($payroll === NULL) throw new ErrorMessage(translator('Payroll not found for the selected period.'));

            $deduction = Deduction::where('id', $id)->where('payroll_id', $payroll->id)->first();
            if ($deduction === NULL) throw new ErrorMessage(translator('Deduction not found.'));

            $deduction->delete();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->loadPayrollData();
        $this->dispatch('refresh-payroll-data')->to(Payroll::class);
        $this->notifyMe(translator('Deduction removed.'));
    }

    /**
     * Load the payroll of the selected period along with the worker options
     * (its items) and the deductions to display in the table.
     *
     * @return void
     */
    private function loadPayrollData(): void
    {
        try {
            $payroll = $this->getPayroll();
            if ($payroll === NULL) throw new ErrorMessage(translator('Payroll not found for the selected period.'));

            $this->locked = (bool) $payroll->locked;
            $this->workers = $payroll->getPayrollItems
                ->mapWithKeys(fn ($item) => [
                    $item->worker_id => str_pad((string) $item->worker_id, 3, '0', STR_PAD_LEFT) . ' - ' . ($item->payroll_data['name'] ?? ('#' . $item->worker_id)),
                ])
                ->sortKeys()
                ->all();

            $this->deductions = $payroll->getDeductions
                ->when($this->presetWorkerID !== NULL, fn ($collection) => $collection->where('worker_id', $this->presetWorkerID))
                ->sortByDesc('id')
                ->map(fn ($deduction) => [
                    'id'          => $deduction->id,
                    'workerLabel' => $this->workers[$deduction->worker_id] ?? ('#' . $deduction->worker_id),
                    'amount'      => $deduction->amount,
                    'reason'      => $deduction->reason,
                ])
                ->values()
                ->all();
        } catch (\Throwable $th) {
            $this->workers = [];
            $this->deductions = [];
            $this->showException($th->getMessage());
        }
    }

    /**
     * Get the payroll of the selected period, with its items and deductions loaded.
     *
     * @return PayrollModel|null
     */
    private function getPayroll(): ?PayrollModel
    {
        return GetPayrollService::byPeriod((int) $this->selectedMonth, (int) $this->selectedYear)
            ->with('getPayrollItems', 'getDeductions')
            ->get();
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.components.payroll-deduction-modal');
    }
}
