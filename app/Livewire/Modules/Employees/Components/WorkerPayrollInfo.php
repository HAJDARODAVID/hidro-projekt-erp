<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Exceptions\ErrorMessage;
use App\Livewire\LivewireController;
use App\Models\Employees\Worker;
use App\Services\Payroll\GetWorkerPayrollInfoService;
use App\Services\Payroll\UpdateWorkerPayrollInfoService;
use App\Services\Payroll\WorkerPayrollInfoDto;

class WorkerPayrollInfo extends LivewireController
{
    /**Fields that save themselves when changed */
    private const SAVEABLE_FIELDS = ['hourRate', 'fixRate', 'travelExpense', 'phoneExpense', 'bonus'];

    public $workerId;

    public $hourRate = 0;
    public $fixRate = NULL;
    public $travelExpense = 0;
    public $phoneExpense = 0;
    public $bonus = FALSE;

    public function mount()
    {
        $this->loadPayrollInfo();
    }

    /**
     * Run when a field is changed (on blur for the amounts, right away for the bonus
     * toggle). Saves the payroll settings, marks the changed field as saved (is-valid)
     * and refreshes the payroll table behind the modal, if one is listening.
     *
     * @param mixed $value
     * @param string $property
     * @return void
     */
    public function updated($property, $value)
    {
        if (!in_array($property, self::SAVEABLE_FIELDS, TRUE)) return;

        $this->saved = [];

        try {
            $hourRate = $this->normalizeAmount($this->hourRate, translator('Enter a valid hourly rate.'));
            $fixRate = $this->fixRate === NULL || $this->fixRate === '' ? NULL : $this->normalizeAmount($this->fixRate, translator('Enter a valid fixed rate.'));
            $travelExpense = $this->normalizeAmount($this->travelExpense, translator('Enter a valid travel expense.'));
            $phoneExpense = $this->normalizeAmount($this->phoneExpense, translator('Enter a valid phone expense.'));

            $service = (new UpdateWorkerPayrollInfoService((int) $this->workerId))
                ->execute($hourRate, $fixRate, $travelExpense, $phoneExpense, (bool) $this->bonus);
            if (!$service->getResponseStatus()) throw new ErrorMessage($service->getResponse()['message']);
        } catch (\Throwable $th) {
            $this->saved[$property] = FALSE;
            return $this->showException($th->getMessage());
        }

        $this->applyPayrollInfo($service->getResponse()['data']);
        $this->saved[$property] = TRUE;
        $this->dispatch('refresh-payroll-data');
        $this->notifyMe(translator('Payroll info for: ') . Worker::find($this->workerId)->fullName . translator(', successfully saved!.'));
    }

    /**
     * Load the worker's payroll settings into the form.
     *
     * @return void
     */
    private function loadPayrollInfo(): void
    {
        $dto = GetWorkerPayrollInfoService::byWorker((int) $this->workerId);
        if ($dto !== NULL) $this->applyPayrollInfo($dto);
    }

    /**
     * Fill the form with the payroll settings of the DTO.
     *
     * @param WorkerPayrollInfoDto $dto
     * @return void
     */
    private function applyPayrollInfo(WorkerPayrollInfoDto $dto): void
    {
        $this->hourRate = $dto->getHourRate();
        $this->fixRate = $dto->getFixRate();
        $this->travelExpense = $dto->getTravelExpense();
        $this->phoneExpense = $dto->getPhoneExpense();
        $this->bonus = $dto->getBonus();
    }

    /**
     * Validate and normalize a monetary field: blank becomes 0, anything else must
     * be a non-negative number.
     *
     * @param mixed $value
     * @param string $errorMessage
     * @return float
     * @throws ErrorMessage
     */
    private function normalizeAmount($value, string $errorMessage): float
    {
        if ($value === NULL || $value === '') return 0.0;
        if (!is_numeric($value) || (float) $value < 0) throw new ErrorMessage($errorMessage);
        return (float) $value;
    }

    public function render()
    {
        return view('livewire.modules.employees.components.worker-payroll-info');
    }
}
