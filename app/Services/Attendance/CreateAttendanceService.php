<?php

namespace App\Services\Attendance;

use App\Models\WorkerModel;
use App\Models\AttendanceCoOpModel;
use App\Models\CooperatorWorkersModel;
use App\Models\AttendanceModel;

/**
 * Class CreateAttendanceService.php.
 */
class CreateAttendanceService
{
    const MY_WORKER_TYPE = 'myWorker';
    const COOPERATOR_TYPE = 'cooperator';
    const AVAILABLE_TYPES = ['myWorker', 'cooperator'];

    private $for;

    private $workerID      = NULL;
    private $diaryID       = NULL;
    private $type          = NULL;
    private $workHours     = NULL;
    private $absenceReason = NULL;
    private $date          = NULL;

    public function __construct($for)
    {
        $this->for = $this->setFor($for);
    }

    private function setFor($for)
    {
        if (in_array($for, self::AVAILABLE_TYPES)) {
            return $for;
        }
        return NULL;
    }

    public static function myWorker()
    {
        return new self(self::MY_WORKER_TYPE);
    }

    public static function cooperator()
    {
        return new self(self::COOPERATOR_TYPE);
    }

    public function execute()
    {
        $worker = NULL;
        $attendance = NULL;
        if ($this->workerID) {
            if ($this->for == self::MY_WORKER_TYPE) {
                $worker = WorkerModel::find($this->workerID);
                $attendance = new AttendanceModel;
            }
            if ($this->for == self::COOPERATOR_TYPE) {
                $worker = CooperatorWorkersModel::find($this->workerID);
                $attendance = new AttendanceCoOpModel;
            }
        }
        if ($worker == NULL) return ['success' => false, 'error' => "Ne postoji zapis radnika sa ID:" . $this->workerID . "!"];
        $attendance->create([
            'worker_id' => $this->workerID,
            'working_day_record_id' => $this->diaryID,
            'type' => $this->type,
            'work_hours' => $this->workHours,
            'absence_reason' => $this->absenceReason,
            'date' => $this->date,
        ]);
        return ['success' => true];
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
     * Get the value of diaryID
     */
    public function getDiaryID()
    {
        return $this->diaryID;
    }

    /**
     * Set the value of diaryID
     *
     * @return  self
     */
    public function setDiaryID($diaryID)
    {
        $this->diaryID = $diaryID;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * Get the value of date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date
     *
     * @return  self
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }
}
