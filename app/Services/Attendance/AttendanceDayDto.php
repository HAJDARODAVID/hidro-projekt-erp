<?php

namespace App\Services\Attendance;

use App\Services\BaseDTO;

/**
 * Class AttendanceDayDto.
 */
class AttendanceDayDto extends BaseDTO
{
    /** Attendance record ID */
    protected $id;

    /** Construction site name, formatted as "user-name | construction site" */
    protected $constructionSiteName;

    /** Working hours logged for the day */
    protected $workingHours;

    /** Absence reason code */
    protected $absenceReason;

    /** Work type code */
    protected $workType;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of constructionSiteName
     */
    public function getConstructionSiteName()
    {
        return $this->constructionSiteName;
    }

    /**
     * Set the value of constructionSiteName
     *
     * @return  self
     */
    public function setConstructionSiteName($constructionSiteName)
    {
        $this->constructionSiteName = $constructionSiteName;

        return $this;
    }

    /**
     * Get the value of workingHours
     */
    public function getWorkingHours()
    {
        return $this->workingHours;
    }

    /**
     * Set the value of workingHours
     *
     * @return  self
     */
    public function setWorkingHours($workingHours)
    {
        $this->workingHours = $workingHours;

        return $this;
    }

    /**
     * Get the value of absenceReason
     */
    public function getAbsenceReason()
    {
        return $this->absenceReason;
    }

    /**
     * Set the value of absenceReason
     *
     * @return  self
     */
    public function setAbsenceReason($absenceReason)
    {
        $this->absenceReason = $absenceReason;

        return $this;
    }

    /**
     * Get the value of workType
     */
    public function getWorkType()
    {
        return $this->workType;
    }

    /**
     * Set the value of workType
     *
     * @return  self
     */
    public function setWorkType($workType)
    {
        $this->workType = $workType;

        return $this;
    }
}
