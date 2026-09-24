<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Services\BaseService;
use App\Services\Attendance\MonthlyHoursOverviewReportDto;

/**
 * Class CalculateWorkerPayrollService.
 * Takes the monthly hours overview of one worker, pulls the worker payroll info
 * (hour rate, fix rate, expenses, bonus) and calculates the payroll for that month.
 *
 * Calculation, the base and net rules are user defined (see PayrollCalculationConfigDto):
 *  - hours       = sum of the hour sources selected in the config (work hours, PL, HD, SL)
 *  - base        = fixRate when set and enabled in the config, otherwise hours * hourRate
 *  - homeBonus   = home days  * homeDayBonus
 *  - fieldBonus  = field days * fieldDayBonus
 *  - bonus       = monthlyBonus when the worker is eligible and has no sick leave, otherwise 0
 *  - gross       = sum of the components set to "add" in the config
 *  - net         = gross - sum of the components set to "subtract" in the config
 */
class CalculateWorkerPayrollService extends BaseService
{
    /** @var MonthlyHoursOverviewReportDto */
    protected $hoursDto;

    /** @var WorkerPayrollInfoDto|null */
    protected $payrollInfo = NULL;

    /** @var PayrollBonusConfigDto|null */
    protected $bonusConfig = NULL;

    /** @var PayrollCalculationConfigDto|null */
    protected $calculationConfig = NULL;

    /**Sum of deductions [€] for the worker, positive amount */
    protected $deductions = 0.0;

    /**Values changed on the payroll itself (saved payroll item), they replace the payroll info values */
    protected ?PayrollEditableValuesDto $editableValues = NULL;

    public function __construct(MonthlyHoursOverviewReportDto $hoursDto)
    {
        $this->hoursDto = $hoursDto;
    }

    /**
     * Pass an already loaded payroll info to skip the lookup.
     *
     * @param WorkerPayrollInfoDto $payrollInfo
     * @return self
     */
    public function setPayrollInfo(WorkerPayrollInfoDto $payrollInfo): self
    {
        $this->payrollInfo = $payrollInfo;
        return $this;
    }

    /**
     * Pass an already loaded bonus config to skip the lookup (use when calculating many workers).
     *
     * @param PayrollBonusConfigDto $bonusConfig
     * @return self
     */
    public function setBonusConfig(PayrollBonusConfigDto $bonusConfig): self
    {
        $this->bonusConfig = $bonusConfig;
        return $this;
    }

    /**
     * Pass an already loaded calculation config to skip the lookup (use when calculating many workers).
     *
     * @param PayrollCalculationConfigDto $calculationConfig
     * @return self
     */
    public function setCalculationConfig(PayrollCalculationConfigDto $calculationConfig): self
    {
        $this->calculationConfig = $calculationConfig;
        return $this;
    }

    /**
     * Set the sum of deductions for the worker.
     *
     * @param float $deductions
     * @return self
     */
    public function setDeductions(float $deductions): self
    {
        $this->deductions = abs($deductions);
        return $this;
    }

    /**
     * Pass the values changed on the payroll (hourly rate, travel expense, phone expense, bonus).
     * Set properties replace the payroll info values, NULL properties are ignored.
     *
     * @param PayrollEditableValuesDto|null $editableValues
     * @return self
     */
    public function setEditableValues(?PayrollEditableValuesDto $editableValues): self
    {
        $this->editableValues = $editableValues;
        return $this;
    }

    /**
     * Calculate the payroll. The data is a WorkerPayrollCalculationDto.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $workerID = $this->hoursDto->getWorkerID();
            if ($workerID === NULL) throw new ErrorMessage('Worker ID is not set on the monthly hours DTO.');

            $this->loadPayrollInfo((int) $workerID)->loadBonusConfig()->loadCalculationConfig()->applyEditableValues();

            $homeDays   = (int) $this->hoursDto->getWorkHome();
            $fieldDays  = (int) $this->hoursDto->getWorkField();
            $sickDays   = (int) $this->hoursDto->getSickLeave();
            $paidDays   = (int) $this->hoursDto->getPaidLeave();
            $holiDays   = (int) $this->hoursDto->getHoliday();

            $workHours  = (float) $this->hoursDto->getWorkHours();
            $hours      = $this->calculationConfig->calculateBaseHours(
                $workHours,
                $paidDays,
                $holiDays,
                $sickDays
            );

            $base       = $this->calculationConfig->getUseFixRate() && $this->payrollInfo->hasFixRate()
                ? (float) $this->payrollInfo->getFixRate()
                : $hours * (float) $this->payrollInfo->getHourRate();

            $homeBonus  = $homeDays * $this->bonusConfig->getHomeDayBonus();
            $fieldBonus = $fieldDays * $this->bonusConfig->getFieldDayBonus();
            $bonus      = $this->getBonusAmount($sickDays);

            $totals     = $this->calculationConfig->calculateGrossAndNet([
                PayrollCalculationConfigDto::COMPONENT_BASE           => $base,
                PayrollCalculationConfigDto::COMPONENT_HOME_BONUS     => $homeBonus,
                PayrollCalculationConfigDto::COMPONENT_FIELD_BONUS    => $fieldBonus,
                PayrollCalculationConfigDto::COMPONENT_BONUS          => $bonus,
                PayrollCalculationConfigDto::COMPONENT_TRAVEL_EXPENSE => (float) $this->payrollInfo->getTravelExpense(),
                PayrollCalculationConfigDto::COMPONENT_PHONE_EXPENSE  => (float) $this->payrollInfo->getPhoneExpense(),
                PayrollCalculationConfigDto::COMPONENT_DEDUCTIONS     => $this->deductions,
            ]);
            $gross      = $totals['gross'];
            $net        = $totals['net'];

            $dto = (new WorkerPayrollCalculationDto())
                ->setWorkerID($workerID)
                ->setName($this->hoursDto->getName())
                ->setStatus($this->hoursDto->getStatus())
                ->setWorkHours($workHours)
                ->setHours($hours)
                ->setHourRate((float) $this->payrollInfo->getHourRate())
                ->setFixRate($this->payrollInfo->getFixRate())
                ->setBase(round($base, 2))
                ->setHomeDays($homeDays)
                ->setFieldDays($fieldDays)
                ->setHomeBonus(round($homeBonus, 2))
                ->setFieldBonus(round($fieldBonus, 2))
                ->setBonus(round($bonus, 2))
                ->setTravelExpense((float) $this->payrollInfo->getTravelExpense())
                ->setPhoneExpense((float) $this->payrollInfo->getPhoneExpense())
                ->setDeductions(round($this->deductions, 2))
                ->setGross(round($gross, 2))
                ->setNet(round($net, 2))
                ->setSickLeaveDays($sickDays)
                ->setPaidLeaveDays($paidDays)
                ->setHolidayDays($holiDays)
                ->setMissingPayrollInfo(!$this->payrollInfo->getHasPayrollInfo());

            $this->setData($dto);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Load the worker payroll info unless it was passed in.
     *
     * @param int $workerID
     * @return self
     */
    private function loadPayrollInfo(int $workerID): self
    {
        if ($this->payrollInfo !== NULL) return $this;

        $service = (new GetWorkerPayrollInfoService($workerID))->execute();
        if (!$service->getResponseStatus()) throw new ErrorMessage($service->getResponse()['message']);

        $this->payrollInfo = $service->getResponse()['data'];
        return $this;
    }

    /**
     * Load the bonus config unless it was passed in.
     *
     * @return self
     */
    private function loadBonusConfig(): self
    {
        if ($this->bonusConfig === NULL) $this->bonusConfig = PayrollBonusConfigDto::load();
        return $this;
    }

    /**
     * Load the calculation config unless it was passed in.
     *
     * @return self
     */
    private function loadCalculationConfig(): self
    {
        if ($this->calculationConfig === NULL) $this->calculationConfig = PayrollCalculationConfigDto::load();
        return $this;
    }

    /**
     * Replace the payroll info values with the ones changed on the payroll.
     * The loaded payroll info is cloned so the passed in DTO stays untouched.
     *
     * @return self
     */
    private function applyEditableValues(): self
    {
        if ($this->editableValues === NULL) return $this;

        $this->payrollInfo = clone $this->payrollInfo;
        if ($this->editableValues->getHourRate() !== NULL)      $this->payrollInfo->setHourRate($this->editableValues->getHourRate());
        if ($this->editableValues->getTravelExpense() !== NULL) $this->payrollInfo->setTravelExpense($this->editableValues->getTravelExpense());
        if ($this->editableValues->getPhoneExpense() !== NULL)  $this->payrollInfo->setPhoneExpense($this->editableValues->getPhoneExpense());

        return $this;
    }

    /**
     * The bonus amount: the one changed on the payroll when set,
     * otherwise the monthly bonus when the worker is eligible.
     *
     * @param int $sickDays
     * @return float
     */
    private function getBonusAmount(int $sickDays): float
    {
        if ($this->editableValues !== NULL && $this->editableValues->getBonus() !== NULL) {
            return $this->editableValues->getBonus();
        }
        return $this->isEligibleForBonus($sickDays) ? $this->bonusConfig->getMonthlyBonus() : 0.0;
    }

    /**
     * The worker gets the monthly bonus when the payroll info allows it,
     * the hours report did not revoke it, and there was no sick leave.
     *
     * @param int $sickDays
     * @return bool
     */
    private function isEligibleForBonus(int $sickDays): bool
    {
        return (bool) $this->payrollInfo->getBonus()
            && (bool) $this->hoursDto->getBonus()
            && $sickDays == 0;
    }
}
