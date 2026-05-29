<?php

namespace App\Services\Attendance;

use App\Exceptions\ErrorMessage;
use App\Models\AttendanceCoOpModel;
use App\Models\AttendanceModel;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\BaseService;

/**
 * This class will give you the option to update parts of the attendance model.
 * After every method call a update on the model is made, and a response is created. 
 */
class AttendanceUpdateService extends BaseService
{
    const COMPANY_WORKER = 'c-wrk';
    const COOPERATOR     = 'co-op';
    const AVAILABLE_TYPES = [self::COMPANY_WORKER, self::COOPERATOR];

    /** @var AttendanceModel|AttendanceCoOpModel*/
    private $attendance;

    public function __construct($attID, $model = self::COMPANY_WORKER)
    {
        if (!in_array($model, self::AVAILABLE_TYPES)) throw new ErrorMessage('Not a valid model type given!');
        switch ($model) {
            case self::COMPANY_WORKER:
                $this->attendance = AttendanceModel::find($attID);
                break;
            case self::COOPERATOR:
                $this->attendance = AttendanceCoOpModel::find($attID);
                break;
        }
        if (is_null($this->attendance)) throw new ErrorMessage('There is no dataset in the model for the ID:' . $attID . '!');
    }

    /**
     * Initialize a new object via static method for the company worker attendance model.
     * 
     * @param int $attID Pass thru the attendance ID
     * @return AttendanceUpdateService
     */
    public static function worker(int $attID): self
    {
        return new self($attID, self::COMPANY_WORKER);
    }

    /**
     * Initialize a new object via static method for the cooperator attendance model.
     * 
     * @param int $attID Pass thru the attendance ID
     * @return AttendanceUpdateService
     */
    public static function cooperator(int $attID): self
    {
        return new self($attID, self::COOPERATOR);
    }

    /**
     * Update the work hours.
     * 
     * @return AttendanceUpdateService
     */
    public function updateWorkHours($newValue): self
    {
        /**Set the type to NULL in case a absence is set */
        $this->attendance->absence_reason = NULL;

        $this->attendance->work_hours = $newValue;
        $this->attendance->save();
        $this->setSuccessTrue();
        return $this;
    }

    /**
     * Update the type.
     * 
     * @return AttendanceUpdateService
     */
    public function updateType($newValue): self
    {
        if ($newValue == '' || $newValue * 1 == 0) $newValue = NULL;
        $this->attendance->type = $newValue;
        $this->attendance->save();
        $this->setSuccessTrue();
        return $this;
    }

    /**
     * Update the absence reason.
     * 
     * @param AttendanceAbsenceType $newValue give the new absence type in a AttendanceAbsenceType::class
     * @return AttendanceUpdateService
     */
    public function updateAbsence(AttendanceAbsenceType $newValue): self
    {
        /**Set the hours and type to NULL in case there are data set */
        $this->attendance->work_hours = NULL;
        $this->attendance->type = NULL;

        $this->attendance->absence_reason = $newValue->code();
        $this->attendance->save();
        $this->setSuccessTrue();
        return $this;
    }

    /**
     * Remove the work diary ID from the attendance.
     * 
     * @return AttendanceUpdateService
     */
    public function removeWorkDiary(): self
    {
        $this->attendance->working_day_record_id = \NULL;
        $this->attendance->save();
        $this->setSuccessTrue();
        return $this;
    }

    /**
     * Update the work diary ID .
     * 
     * @return AttendanceUpdateService
     */
    public function updateWorkDiary($newValue): self
    {
        $this->attendance->working_day_record_id = $newValue;
        $this->attendance->save();
        $this->setSuccessTrue();
        return $this;
    }
}
