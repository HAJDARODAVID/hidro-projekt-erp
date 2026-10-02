<?php

namespace App\Services\Payroll;

use App\Services\BaseDTO;
use App\Models\PayrollBasicInfoModel;

/**
 * Class WorkerPayrollInfoDto.
 * Holds the payroll settings of one worker (hour rate, fix rate, additional expenses, bonus eligibility).
 */
class WorkerPayrollInfoDto extends BaseDTO
{
    /**Worker ID */
    protected $workerID;

    /**Hourly rate [€/h] */
    protected $hourRate = 0.0;

    /**Fixed monthly rate [€]. When set it replaces hours * hourRate as the base. */
    protected $fixRate = NULL;

    /**Monthly travel expense [€] */
    protected $travelExpense = 0.0;

    /**Monthly phone expense [€] */
    protected $phoneExpense = 0.0;

    /**Is the worker eligible for the monthly bonus */
    protected $bonus = FALSE;

    /**TRUE when a payroll info record exists for the worker */
    protected $hasPayrollInfo = FALSE;

    /**
     * Create a new instance from the payroll basic info model.
     *
     * @param PayrollBasicInfoModel $model
     * @return self
     */
    public static function fromModel(PayrollBasicInfoModel $model): self
    {
        return (new self())
            ->setWorkerID($model->worker_id)
            ->setHourRate((float) ($model->h_rate ?? 0))
            ->setFixRate($model->fix_rate !== NULL ? (float) $model->fix_rate : NULL)
            ->setTravelExpense((float) ($model->travel_exp ?? 0))
            ->setPhoneExpense((float) ($model->phone_exp ?? 0))
            ->setBonus((bool) $model->bonus)
            ->setHasPayrollInfo(TRUE);
    }

    /**
     * Does the worker have a fixed monthly rate.
     *
     * @return bool
     */
    public function hasFixRate(): bool
    {
        return $this->fixRate !== NULL && $this->fixRate > 0;
    }

    /**
     * Get the value of workerID
     */
    public function getWorkerID()
    {
        return $this->workerID;
    }

    /**
     * Set the value of workerID
     *
     * @return  self
     */
    public function setWorkerID($workerID)
    {
        $this->workerID = $workerID;

        return $this;
    }

    /**
     * Get the value of hourRate
     */
    public function getHourRate()
    {
        return $this->hourRate;
    }

    /**
     * Set the value of hourRate
     *
     * @return  self
     */
    public function setHourRate($hourRate)
    {
        $this->hourRate = $hourRate;

        return $this;
    }

    /**
     * Get the value of fixRate
     */
    public function getFixRate()
    {
        return $this->fixRate;
    }

    /**
     * Set the value of fixRate
     *
     * @return  self
     */
    public function setFixRate($fixRate)
    {
        $this->fixRate = $fixRate;

        return $this;
    }

    /**
     * Get the value of travelExpense
     */
    public function getTravelExpense()
    {
        return $this->travelExpense;
    }

    /**
     * Set the value of travelExpense
     *
     * @return  self
     */
    public function setTravelExpense($travelExpense)
    {
        $this->travelExpense = $travelExpense;

        return $this;
    }

    /**
     * Get the value of phoneExpense
     */
    public function getPhoneExpense()
    {
        return $this->phoneExpense;
    }

    /**
     * Set the value of phoneExpense
     *
     * @return  self
     */
    public function setPhoneExpense($phoneExpense)
    {
        $this->phoneExpense = $phoneExpense;

        return $this;
    }

    /**
     * Get the value of bonus
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set the value of bonus
     *
     * @return  self
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get the value of hasPayrollInfo
     */
    public function getHasPayrollInfo()
    {
        return $this->hasPayrollInfo;
    }

    /**
     * Set the value of hasPayrollInfo
     *
     * @return  self
     */
    public function setHasPayrollInfo($hasPayrollInfo)
    {
        $this->hasPayrollInfo = $hasPayrollInfo;

        return $this;
    }
}
