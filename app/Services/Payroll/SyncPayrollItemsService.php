<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Payroll\Items;
use App\Models\Payroll\Payroll;
use App\Services\BaseService;

/**
 * Class SyncPayrollItemsService.
 * Keeps the payroll items of one month/year in sync with the payroll calculation.
 * It does not calculate anything, that is the job of the CalculateWorkerPayrollService:
 *  - the payroll of the period is loaded, or created when it does not exist yet
 *  - for a worker with a saved item it hands over the values that can change on the payroll
 *    (hourly rate, travel expense, phone expense, bonus) so the calculation uses them
 *  - for a worker without an item it creates one from the calculation result
 *
 * Usage:
 *  $sync = (new SyncPayrollItemsService($month, $year))->execute();
 *  $calculation = (new CalculateWorkerPayrollService($hoursDto))
 *      ->setEditableValues($sync->getEditableValues($workerID))
 *      ->execute();
 *  $sync->createItemIfMissing($workerID, $calculation->getResponse()['data']);
 */
class SyncPayrollItemsService extends BaseService
{
    /** @var int */
    protected $month;

    /** @var int */
    protected $year;

    /** @var Payroll|null */
    protected $payroll = NULL;

    /**Payroll items of the period keyed by worker ID */
    protected array $items = [];

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Load the payroll of the period (create it when missing) and its items.
     * The data is the Payroll model.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $this->payroll = GetPayrollService::byPeriod($this->month, $this->year)->with('getPayrollItems')->get();

            if ($this->payroll === NULL) {
                $create = (new CreatePayrollService($this->month, $this->year))->execute();
                if (!$create->getResponseStatus()) throw new ErrorMessage($create->getResponse()['message']);
                $this->payroll = $create->getResponse()['data']->load('getPayrollItems');
            }

            $this->items = $this->payroll->getPayrollItems->keyBy('worker_id')->all();

            $this->setData($this->payroll);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Get the payroll of the period.
     *
     * @return Payroll|null
     */
    public function getPayroll(): ?Payroll
    {
        return $this->payroll;
    }

    /**
     * Does the worker have a payroll item on this payroll.
     *
     * @param int $workerID
     * @return bool
     */
    public function hasItem(int $workerID): bool
    {
        return isset($this->items[$workerID]);
    }

    /**
     * Get the payroll item of the worker.
     *
     * @param int $workerID
     * @return Items|null
     */
    public function getItem(int $workerID): ?Items
    {
        return $this->items[$workerID] ?? NULL;
    }

    /**
     * Get the editable values stored in the payroll item of the worker.
     * NULL when the worker has no item, so the calculation uses the payroll info.
     *
     * @param int $workerID
     * @return PayrollEditableValuesDto|null
     */
    public function getEditableValues(int $workerID): ?PayrollEditableValuesDto
    {
        $item = $this->getItem($workerID);
        return $item ? PayrollEditableValuesDto::fromPayrollItem($item) : NULL;
    }

    /**
     * Create the payroll item of the worker from the calculation when there is none yet.
     * A locked payroll gets no new items.
     *
     * @param int $workerID
     * @param WorkerPayrollCalculationDto $calculation
     * @return self
     * @throws ErrorMessage
     */
    public function createItemIfMissing(int $workerID, WorkerPayrollCalculationDto $calculation): self
    {
        if ($this->payroll === NULL) throw new ErrorMessage('The payroll of the period is not loaded, run execute() first.');
        if ($this->hasItem($workerID) || $this->payroll->locked) return $this;

        $create = (new CreatePayrollItemService($this->payroll, $workerID))->setPayrollData($calculation)->execute();
        if (!$create->getResponseStatus()) throw new ErrorMessage($create->getResponse()['message']);

        $this->items[$workerID] = $create->getResponse()['data'];
        return $this;
    }
}
