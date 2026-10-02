<?php

namespace App\Services\Attendance;

use App\Services\BaseDTO;

/**
 * Class MonthlyHoursOverviewReportDto.
 * Represents one worker row produced by the MonthlyHoursOverviewReportService.
 */
class MonthlyHoursOverviewReportDto extends BaseDTO
{
    /**Map of the report array keys to the DTO properties */
    const ARRAY_KEYS = [
        'worker-id'        => 'workerID',
        'name'             => 'name',
        'status'           => 'status',
        'work-hours'       => 'workHours',
        'work-hours-total' => 'workHoursTotal',
        'bonus'            => 'bonus',
        'work-home'        => 'workHome',
        'work-field'       => 'workField',
        'work-misc'        => 'workMisc',
        'SL'               => 'sickLeave',
        'PL'               => 'paidLeave',
        'HD'               => 'holiday',
    ];

    /**Worker ID (array key of the report) */
    protected $workerID;

    /**Worker full name */
    protected $name;

    /**Worker status */
    protected $status;

    /**Sum of the logged work hours */
    protected $workHours = 0;

    /**Work hours including paid leave and holiday hours */
    protected $workHoursTotal = 0;

    /**Bonus eligibility flag */
    protected $bonus = TRUE;

    /**Number of days worked at home */
    protected $workHome = 0;

    /**Number of days worked in the field */
    protected $workField = 0;

    /**Number of days with misc work */
    protected $workMisc = 0;

    /**Number of sick leave days (SL) */
    protected $sickLeave = 0;

    /**Number of paid leave days (PL) */
    protected $paidLeave = 0;

    /**Number of holiday days (HD) */
    protected $holiday = 0;

    /**
     * Create a new instance from the report array structure.
     * Unknown keys are ignored, missing keys keep the default values.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        foreach (self::ARRAY_KEYS as $key => $property) {
            if (array_key_exists($key, $data)) $dto->{$property} = $data[$key];
        }
        return $dto;
    }

    /**
     * Get the properties in the original report array structure.
     *
     * @return array
     */
    public function toReportArray(): array
    {
        $output = [];
        foreach (self::ARRAY_KEYS as $key => $property) {
            $output[$key] = $this->{$property};
        }
        return $output;
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
     * Get the value of workHours
     */
    public function getWorkHours()
    {
        return $this->workHours;
    }

    /**
     * Set the value of workHours
     *
     * @return  self
     */
    public function setWorkHours($workHours)
    {
        $this->workHours = $workHours;

        return $this;
    }

    /**
     * Get the value of workHoursTotal
     */
    public function getWorkHoursTotal()
    {
        return $this->workHoursTotal;
    }

    /**
     * Set the value of workHoursTotal
     *
     * @return  self
     */
    public function setWorkHoursTotal($workHoursTotal)
    {
        $this->workHoursTotal = $workHoursTotal;

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
     * Get the value of workHome
     */
    public function getWorkHome()
    {
        return $this->workHome;
    }

    /**
     * Set the value of workHome
     *
     * @return  self
     */
    public function setWorkHome($workHome)
    {
        $this->workHome = $workHome;

        return $this;
    }

    /**
     * Get the value of workField
     */
    public function getWorkField()
    {
        return $this->workField;
    }

    /**
     * Set the value of workField
     *
     * @return  self
     */
    public function setWorkField($workField)
    {
        $this->workField = $workField;

        return $this;
    }

    /**
     * Get the value of workMisc
     */
    public function getWorkMisc()
    {
        return $this->workMisc;
    }

    /**
     * Set the value of workMisc
     *
     * @return  self
     */
    public function setWorkMisc($workMisc)
    {
        $this->workMisc = $workMisc;

        return $this;
    }

    /**
     * Get the value of sickLeave (SL)
     */
    public function getSickLeave()
    {
        return $this->sickLeave;
    }

    /**
     * Set the value of sickLeave (SL)
     *
     * @return  self
     */
    public function setSickLeave($sickLeave)
    {
        $this->sickLeave = $sickLeave;

        return $this;
    }

    /**
     * Get the value of paidLeave (PL)
     */
    public function getPaidLeave()
    {
        return $this->paidLeave;
    }

    /**
     * Set the value of paidLeave (PL)
     *
     * @return  self
     */
    public function setPaidLeave($paidLeave)
    {
        $this->paidLeave = $paidLeave;

        return $this;
    }

    /**
     * Get the value of holiday (HD)
     */
    public function getHoliday()
    {
        return $this->holiday;
    }

    /**
     * Set the value of holiday (HD)
     *
     * @return  self
     */
    public function setHoliday($holiday)
    {
        $this->holiday = $holiday;

        return $this;
    }
}
