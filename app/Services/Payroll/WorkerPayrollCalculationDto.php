<?php

namespace App\Services\Payroll;

use App\Services\BaseDTO;

/**
 * Class WorkerPayrollCalculationDto.
 * The calculated payroll of one worker for one month.
 */
class WorkerPayrollCalculationDto extends BaseDTO
{
    /**Worker ID */
    protected $workerID;

    /**Worker full name */
    protected $name;

    /**Worker status */
    protected $status;

    /**Hours used for the calculation (work hours + paid leave + holiday) */
    protected $hours = 0.0;

    /**Hourly rate [€/h] */
    protected $hourRate = 0.0;

    /**Fixed monthly rate [€], NULL when the worker is paid by the hour */
    protected $fixRate = NULL;

    /**Base amount: fixRate, or hours * hourRate [€] */
    protected $base = 0.0;

    /**Number of days worked at home */
    protected $homeDays = 0;

    /**Number of days worked in the field */
    protected $fieldDays = 0;

    /**Bonus for the home days [€] */
    protected $homeBonus = 0.0;

    /**Bonus for the field days [€] */
    protected $fieldBonus = 0.0;

    /**Monthly bonus [€], 0 when the worker is not eligible or had sick leave */
    protected $bonus = 0.0;

    /**Travel expense [€] */
    protected $travelExpense = 0.0;

    /**Phone expense [€] */
    protected $phoneExpense = 0.0;

    /**Sum of deductions [€], stored as a positive amount */
    protected $deductions = 0.0;

    /**Gross amount: base + all bonuses + expenses [€] */
    protected $gross = 0.0;

    /**Net amount: gross - deductions [€] */
    protected $net = 0.0;

    /**Number of sick leave days */
    protected $sickLeaveDays = 0;

    /**Number of paid leave days (PL) */
    protected $paidLeaveDays = 0;

    /**Number of holiday days (HD) */
    protected $holidayDays = 0;

    /**TRUE when the worker has no payroll info record */
    protected $missingPayrollInfo = FALSE;

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
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return  self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of hours
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * Set the value of hours
     *
     * @return  self
     */
    public function setHours($hours)
    {
        $this->hours = $hours;

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
     * Get the value of base
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set the value of base
     *
     * @return  self
     */
    public function setBase($base)
    {
        $this->base = $base;

        return $this;
    }

    /**
     * Get the value of homeDays
     */
    public function getHomeDays()
    {
        return $this->homeDays;
    }

    /**
     * Set the value of homeDays
     *
     * @return  self
     */
    public function setHomeDays($homeDays)
    {
        $this->homeDays = $homeDays;

        return $this;
    }

    /**
     * Get the value of fieldDays
     */
    public function getFieldDays()
    {
        return $this->fieldDays;
    }

    /**
     * Set the value of fieldDays
     *
     * @return  self
     */
    public function setFieldDays($fieldDays)
    {
        $this->fieldDays = $fieldDays;

        return $this;
    }

    /**
     * Get the value of homeBonus
     */
    public function getHomeBonus()
    {
        return $this->homeBonus;
    }

    /**
     * Set the value of homeBonus
     *
     * @return  self
     */
    public function setHomeBonus($homeBonus)
    {
        $this->homeBonus = $homeBonus;

        return $this;
    }

    /**
     * Get the value of fieldBonus
     */
    public function getFieldBonus()
    {
        return $this->fieldBonus;
    }

    /**
     * Set the value of fieldBonus
     *
     * @return  self
     */
    public function setFieldBonus($fieldBonus)
    {
        $this->fieldBonus = $fieldBonus;

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
     * Get the value of deductions
     */
    public function getDeductions()
    {
        return $this->deductions;
    }

    /**
     * Set the value of deductions
     *
     * @return  self
     */
    public function setDeductions($deductions)
    {
        $this->deductions = $deductions;

        return $this;
    }

    /**
     * Get the value of gross
     */
    public function getGross()
    {
        return $this->gross;
    }

    /**
     * Set the value of gross
     *
     * @return  self
     */
    public function setGross($gross)
    {
        $this->gross = $gross;

        return $this;
    }

    /**
     * Get the value of net
     */
    public function getNet()
    {
        return $this->net;
    }

    /**
     * Set the value of net
     *
     * @return  self
     */
    public function setNet($net)
    {
        $this->net = $net;

        return $this;
    }

    /**
     * Get the value of sickLeaveDays
     */
    public function getSickLeaveDays()
    {
        return $this->sickLeaveDays;
    }

    /**
     * Set the value of sickLeaveDays
     *
     * @return  self
     */
    public function setSickLeaveDays($sickLeaveDays)
    {
        $this->sickLeaveDays = $sickLeaveDays;

        return $this;
    }

    /**
     * Get the value of paidLeaveDays (PL)
     */
    public function getPaidLeaveDays()
    {
        return $this->paidLeaveDays;
    }

    /**
     * Set the value of paidLeaveDays (PL)
     *
     * @return  self
     */
    public function setPaidLeaveDays($paidLeaveDays)
    {
        $this->paidLeaveDays = $paidLeaveDays;

        return $this;
    }

    /**
     * Get the value of holidayDays (HD)
     */
    public function getHolidayDays()
    {
        return $this->holidayDays;
    }

    /**
     * Set the value of holidayDays (HD)
     *
     * @return  self
     */
    public function setHolidayDays($holidayDays)
    {
        $this->holidayDays = $holidayDays;

        return $this;
    }

    /**
     * Get the value of missingPayrollInfo
     */
    public function getMissingPayrollInfo()
    {
        return $this->missingPayrollInfo;
    }

    /**
     * Set the value of missingPayrollInfo
     *
     * @return  self
     */
    public function setMissingPayrollInfo($missingPayrollInfo)
    {
        $this->missingPayrollInfo = $missingPayrollInfo;

        return $this;
    }
}
