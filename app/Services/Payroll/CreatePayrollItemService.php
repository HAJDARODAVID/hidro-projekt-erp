<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Worker;
use App\Models\Payroll\Items;
use App\Models\Payroll\Payroll;
use App\Services\BaseService;

/**
 * Class CreatePayrollItemService.
 * Creates one payroll item: the payroll data of one worker on one payroll.
 * The payroll data can be passed as the WorkerPayrollCalculationDto or as a plain array,
 * it is stored in the payroll_data JSON column.
 */
class CreatePayrollItemService extends BaseService
{
    /** @var Payroll */
    protected $payroll;

    /** @var int */
    protected $workerID;

    /**Data stored in the payroll_data column */
    protected array $payrollData = [];

    public function __construct(Payroll $payroll, int $workerID)
    {
        $this->payroll = $payroll;
        $this->workerID = $workerID;
    }

    /**
     * Set the payroll data of the worker.
     *
     * @param WorkerPayrollCalculationDto|array $data
     * @return self
     */
    public function setPayrollData(WorkerPayrollCalculationDto|array $data): self
    {
        $this->payrollData = $data instanceof WorkerPayrollCalculationDto ? $data->toArray() : $data;
        return $this;
    }

    /**
     * Create the payroll item. The data is the created Items model.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $this->validate();

            $item = Items::create([
                'payroll_id'   => $this->payroll->id,
                'worker_id'    => $this->workerID,
                'payroll_data' => $this->payrollData,
            ]);

            $this->setSuccessMessage('Payroll item created!', $item);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Check that the item can be created.
     *
     * @throws ErrorMessage
     */
    private function validate(): void
    {
        if (!$this->payroll->exists) throw new ErrorMessage('The payroll is not saved yet.');
        if ($this->payroll->locked) throw new ErrorMessage('The payroll ' . $this->payroll->month . '/' . $this->payroll->year . ' is locked.');
        if (empty($this->payrollData)) throw new ErrorMessage('The payroll data is not set.');
        if (!Worker::where('id', $this->workerID)->exists()) throw new ErrorMessage('Worker ' . $this->workerID . ' does not exist.');

        $exists = Items::where('payroll_id', $this->payroll->id)->where('worker_id', $this->workerID)->exists();
        if ($exists) throw new ErrorMessage('Worker ' . $this->workerID . ' already has an item on the payroll ' . $this->payroll->month . '/' . $this->payroll->year . '.');
    }
}
