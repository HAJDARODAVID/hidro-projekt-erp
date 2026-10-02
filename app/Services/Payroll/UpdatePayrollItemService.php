<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Worker;
use App\Models\Payroll\Deduction;
use App\Models\Payroll\Items;
use App\Services\Attendance\MonthlyHoursOverviewReportDto;
use App\Services\Attendance\MonthlyHoursOverviewReportService;
use App\Services\BaseService;

/**
 * Class UpdatePayrollItemService.
 * Updates one editable value (hourly rate, travel expense, phone expense, bonus) on a worker's
 * saved payroll item. The whole row is recalculated against the live attendance hours so the
 * saved item stays in sync with what is shown on the payroll table.
 */
class UpdatePayrollItemService extends BaseService
{
    /** @var Items */
    protected $item;

    public function __construct(Items $item)
    {
        $this->item = $item;
    }

    /**
     * Load the payroll item of one worker on one period.
     *
     * @param int $month
     * @param int $year
     * @param int $workerID
     * @return self
     * @throws ErrorMessage
     */
    public static function forWorker(int $month, int $year, int $workerID): self
    {
        $payroll = GetPayrollService::byPeriod($month, $year)->get();
        if ($payroll === NULL) throw new ErrorMessage('Payroll ' . $month . '/' . $year . ' not found.');
        if ($payroll->locked) throw new ErrorMessage('The payroll ' . $month . '/' . $year . ' is locked.');

        $item = GetPayrollItemService::byWorker($payroll, $workerID)->get();
        if ($item === NULL) throw new ErrorMessage('Worker ' . $workerID . ' has no payroll item on ' . $month . '/' . $year . '.');

        return new self($item);
    }

    /**
     * Update the hourly rate.
     *
     * @return self
     */
    public function updateHourRate($value): self
    {
        return $this->updateEditableValue('hourRate', $value);
    }

    /**
     * Update the travel expense.
     *
     * @return self
     */
    public function updateTravelExpense($value): self
    {
        return $this->updateEditableValue('travelExpense', $value);
    }

    /**
     * Update the phone expense.
     *
     * @return self
     */
    public function updatePhoneExpense($value): self
    {
        return $this->updateEditableValue('phoneExpense', $value);
    }

    /**
     * Update the bonus amount.
     *
     * @return self
     */
    public function updateBonus($value): self
    {
        return $this->updateEditableValue('bonus', $value);
    }

    /**
     * Recalculate the row against the worker's payroll info and the live attendance,
     * keeping the values already changed on the item. Use it when a setting the item has
     * no editable value for changed, e.g. the fix rate or the bonus eligibility.
     *
     * @return self
     */
    public function recalculate(): self
    {
        try {
            $this->saveCalculation(PayrollEditableValuesDto::fromPayrollItem($this->item));
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Set one editable value, recalculate the payroll row and save it on the item.
     * The data is the recalculated WorkerPayrollCalculationDto.
     *
     * @param string $property hourRate, travelExpense, phoneExpense or bonus
     * @param mixed $value
     * @return self
     */
    private function updateEditableValue(string $property, $value): self
    {
        try {
            if ($value === NULL || $value === '') {
                $value = 0;
            } elseif (!is_numeric($value) || (float) $value < 0) {
                throw new ErrorMessage('Enter a valid, positive amount.');
            }

            $editableValues = PayrollEditableValuesDto::fromPayrollItem($this->item);
            $editableValues->{'set' . ucfirst($property)}((float) $value);

            $this->saveCalculation($editableValues);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Run the calculation with the given editable values and save it on the item.
     * The data is the recalculated WorkerPayrollCalculationDto.
     *
     * @param PayrollEditableValuesDto $editableValues
     * @return void
     * @throws ErrorMessage
     */
    private function saveCalculation(PayrollEditableValuesDto $editableValues): void
    {
        $calculation = (new CalculateWorkerPayrollService($this->getHoursDto()))
            ->setEditableValues($editableValues)
            ->setDeductions($this->getDeductionsTotal())
            ->execute();
        if (!$calculation->getResponseStatus()) throw new ErrorMessage($calculation->getResponse()['message']);

        /** @var WorkerPayrollCalculationDto $calculationDto */
        $calculationDto = $calculation->getResponse()['data'];

        $this->item->payroll_data = $calculationDto->toArray();
        $this->item->save();

        $this->setData($calculationDto);
    }

    /**
     * Build the worker's hours DTO from the live attendance report for the item's period,
     * falling back to the worker's basic info (zero hours) when there is no attendance yet.
     *
     * @return MonthlyHoursOverviewReportDto
     * @throws ErrorMessage
     */
    private function getHoursDto(): MonthlyHoursOverviewReportDto
    {
        $payroll = $this->item->getPayroll;
        $workerID = $this->item->worker_id;

        $report = (new MonthlyHoursOverviewReportService($payroll->month, $payroll->year))
            ->setSpecificWorkers($workerID)
            ->execute();
        if (!$report->getResponseStatus()) throw new ErrorMessage($report->getResponse()['message']);

        $reportData = $report->getData();
        if (isset($reportData[$workerID])) {
            return MonthlyHoursOverviewReportDto::fromArray($reportData[$workerID])->setWorkerID($workerID);
        }

        $worker = Worker::find($workerID);
        if ($worker === NULL) throw new ErrorMessage('Worker ' . $workerID . ' does not exist.');

        return (new MonthlyHoursOverviewReportDto())
            ->setWorkerID($worker->id)
            ->setName($worker->fullName)
            ->setStatus($worker->status);
    }

    /**
     * Sum of deductions of the worker for this payroll, so editing one value
     * on the row does not wipe out an already applied deduction.
     *
     * @return float
     */
    private function getDeductionsTotal(): float
    {
        return (float) Deduction::where('payroll_id', $this->item->payroll_id)
            ->where('worker_id', $this->item->worker_id)
            ->sum('amount');
    }
}
