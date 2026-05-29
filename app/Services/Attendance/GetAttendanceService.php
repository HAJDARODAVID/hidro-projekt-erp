<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCoOpModel;
use App\Models\AttendanceModel;
use App\Models\CooperatorWorkersModel;
use App\Models\Employees\Attendance;
use App\Models\WorkerModel;
use DateTime;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class CreateAttendanceService.php.
 */
class GetAttendanceService
{
    const MY_WORKER_TYPE = 'myWorker';
    const COOPERATOR_TYPE = 'cooperator';
    const AVAILABLE_TYPES = ['myWorker', 'cooperator'];

    private $myWorkerAttendance   = NULL;
    private $cooperatorAttendance = NULL;

    /**Defines which data will be returned: employees or cooperators*/
    private $output = NULL;

    public function __construct($type, $param)
    {
        switch ($type) {
            case 'byConstructionSite':
                $this->setAttendanceByConstructionSite($param);
                break;
            case 'byWdr':
                $this->setAttendanceByWdr($param);
                break;
            case 'byDate':
                $this->setAttendanceByDate($param);
                break;
            case 'byMonth':
                $this->setAttendanceByMonth($param);
                break;
            case 'byId':
                $this->setAttendanceById($param);
                break;

            default:
                # code...
                break;
        }
    }

    public static function byConstructionSite($id) {}

    public static function byWdr($id)
    {
        return new self('byWdr', $id);
    }

    /**
     * Crete a new instance of this class based on the attendance ID
     * 
     * @param $id Pass thru the attendance ID
     * @return App\Services\Attendance\GetAttendanceService
     */
    public static function byId($id): self
    {
        return new self('byId', $id);
    }

    /**
     * Create a new instance of this object based on the date.
     * 
     * @param DateTime $date Date for which you need the attendance
     * @return App\Services\Attendance\GetAttendanceService
     */
    public static function byDate(DateTime $date)
    {
        return new self('byDate', $date);
    }

    /**
     * Create a new instance of this object based on the month.
     * 
     * @param int $month Month for the report.
     * @param int $year Year for the report.
     * @return App\Services\Attendance\GetAttendanceService
     */
    public static function byMonth(int $month, int|NULL $year = NULL)
    {
        if ($year == NULL) $year = date("Y");
        return new self('byMonth', [$month, $year]);
    }

    //TODO: dok tu bude trebalo za sve WDR uzeti od tog gradilišta
    private function setAttendanceByConstructionSite($id)
    {
        return;
    }

    private function setAttendanceByWdr($param)
    {
        $this->myWorkerAttendance = AttendanceModel::where('working_day_record_id', $param)->get();
        $this->cooperatorAttendance = AttendanceCoOpModel::where('working_day_record_id', $param)->get();
    }

    /**
     * Set the attendance models by the attendance ID
     * 
     * @param $param Pass thru the attendance ID
     * @return void 
     */
    private function setAttendanceById($param): void
    {
        $this->myWorkerAttendance = AttendanceModel::find($param);
        $this->cooperatorAttendance = AttendanceCoOpModel::find($param);
    }

    /**
     * Get all the attendance data for a specific date.
     * 
     * @param DateTime $param Date
     * @return void
     */
    private function setAttendanceByDate(DateTime $param): void
    {
        $this->myWorkerAttendance = Attendance::where('date', $param->format('Y-m-d'))->get();
        $this->cooperatorAttendance = AttendanceCoOpModel::where('date', $param->format('Y-m-d'))->get();
    }

    /**
     * Get all the attendance data for a specific month/year.
     * 
     * @param array $param Array of the month and year
     * @return void
     */
    private function setAttendanceByMonth(array $param): void
    {
        $this->myWorkerAttendance = Attendance::whereYear('date', $param[1])->whereMonth('date', $param[0])->get();
        $this->cooperatorAttendance = AttendanceCoOpModel::whereYear('date', $param[1])->whereMonth('date', $param[0])->get();
    }

    /**
     * Set the $output property the myWorkerAttendance model
     * 
     * @return $this
     */
    public function myEmployees()
    {
        $this->output = $this->myWorkerAttendance;
        return $this;
    }

    /**
     * Set the $output property the cooperatorAttendance model
     * 
     * @return $this
     */
    public function cooperators()
    {
        $this->output = $this->cooperatorAttendance;
        return $this;
    }

    /**
     * Return the number of rows for the attendance
     * 
     * @return int
     */
    public function countAtt(): int
    {
        return is_null($this->output) ? 0 : $this->output->count();
    }

    /**
     * Return the attendance
     * 
     * @return Collection|Attendance|AttendanceCoOpModel
     */
    public function getAtt(): Collection|Attendance|AttendanceCoOpModel
    {
        return $this->output;
    }

    public function getMyWorkerAttendance()
    {
        return $this->myWorkerAttendance;
    }

    public function getCooperatorAttendance()
    {
        return $this->cooperatorAttendance;
    }

    /**
     * Alter the attendance data for the edit attendance component.
     */
    public function createDataForEditAttendanceComponent()
    {
        $myWorkersArray = [];
        $cooperatorsArray = [];
        foreach ($this->myWorkerAttendance as $att) {
            if ($att->work_hours != NULL) {
                $worker = WorkerModel::find($att->worker_id);
                $myWorkersArray[$worker->id] = [
                    'att_id' => $att->id,
                    'name' => $worker->fullName,
                    'att_time' => $att->work_hours
                ];
            }
        }
        foreach ($this->cooperatorAttendance as $att) {
            if ($att->work_hours != NULL) {
                $worker = CooperatorWorkersModel::with('getCoOpInfo')->find($att->worker_id);
                $cooperatorsArray[$worker->id] = [
                    'att_id' => $att->id,
                    'name' => $worker->fullName . '-' . $worker->getCoOpInfo->name,
                    'att_time' => $att->work_hours
                ];
            }
        }
        $this->myWorkerAttendance = $myWorkersArray;
        $this->cooperatorAttendance = $cooperatorsArray;
        return $this;
    }
}
