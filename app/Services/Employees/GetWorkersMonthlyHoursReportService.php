<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Models\Employees\Worker;
use App\Models\Employees\Attendance;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;

/**
 * Class GetWorkersMonthlyHoursReportService.
 */
class GetWorkersMonthlyHoursReportService extends BaseService
{

    /** @var int */
    protected $month;

    /** @var int */
    protected $year;

    /**
     * This will be filled with the attendance data.
     * 
     * @var Collation|Attendance
     */
    private $attendance;

    /**
     * And this will be filled with the workers info data.
     * 
     * @var Array
     */
    private $workers;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Generate the data that will be displayed in the report.
     * Notes: 
     *  - if the current month is bigger then the given, only the workers from the attendance will be show
     * 
     * @return array
     */
    public function execute(): array
    {
        $output = [];
        try {
            /**Get the attendance and workers data */
            $this->getAttendanceData()->getWorkersData();

            /**Go thru the attendance data and to the array thingy */
            foreach ($this->attendance as $att) {
                /**Set the worker info */
                if (!isset($this->workers[$att->worker_id])) continue;
                if (!isset($output[$att->worker_id]['worker-info'])) $output[$att->worker_id]['worker-info'] = $this->workers[$att->worker_id];
                /**Set the array key for the given date if there is non */
                if (!isset($output[$att->worker_id]['attendance'][$att->date])) $output[$att->worker_id]['attendance'][$att->date] = ['hours' => 0, 'absence' => [], 'error' => FALSE];

                /**If the attendance has hours add it to the array by summing */
                if ($att->work_hours != NULL) {
                    $output[$att->worker_id]['attendance'][$att->date]['hours'] += $att->work_hours;
                }

                /**If the absence is set do the thing */
                if ($att->absence_reason != NULL) {
                    $output[$att->worker_id]['attendance'][$att->date]['absence'][] = $att->absence_reason;
                    //dd($att->absence_reason, $output[$att->worker_id]);
                    /**If there is more than one reason set the flag to true */
                    if (count($output[$att->worker_id]['attendance'][$att->date]['absence']) > 1) $output[$att->worker_id]['attendance'][$att->date]['error'] = TRUE;
                }
            }

            /**
             * If you are getting data from the current year and month, put all the workers in the output regardless if thy have attendance data.
             * But only put the active users to the output.
             */
            if ($this->year == now()->format('Y') && $this->month == now()->format('n')) {
                foreach ($this->workers as $id => $wInfo) {
                    if (!key_exists($id, $output) && in_array($wInfo['status'], WorkerStatus::init()->areEmployed())) $output[$id]['worker-info'] = $wInfo;
                }
            }


            /**Put the finished data to the payload response */
            ksort($output);
            $this->setData($output);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }

        return $this->getResponse();
    }

    /**
     * Get all the attendance data for the given month/year.
     * 
     * @return GetWorkersMonthlyHoursReportService
     */
    private function getAttendanceData()
    {
        $this->attendance = Attendance::whereMonth('date', $this->month)->whereYear('date', $this->year)->get();
        return $this;
    }

    /**
     * Get all the workers who can be in the attendance.
     * 
     * @return GetWorkersMonthlyHoursReportService
     */
    private function getWorkersData()
    {
        $output = [];
        $workersCollation = Worker::get();
        $attendanceTypes = WorkerType::init()->getTypesForAttendance();
        foreach ($workersCollation as $worker) {
            if (in_array($worker->type, $attendanceTypes)) {
                $output[$worker->id] = [
                    'name' => $worker->fullName,
                    'status' => $worker->status,
                ];
            }
        }
        $this->workers = $output;
        return $this;
    }
}
