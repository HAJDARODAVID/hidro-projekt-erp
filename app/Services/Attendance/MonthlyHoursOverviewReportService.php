<?php

namespace App\Services\Attendance;

use App\Services\BaseService;
use App\Models\Employees\Attendance;
use App\Models\Employees\AttendanceAbsenceType;
use App\Models\Employees\Worker;
use App\Services\Attendance\GetAttendanceService;
use App\Services\WorkdayDiary\Types;

/**
 * This service will get the data from the attendance 
 * and create a structure suitable for a report.
 */
class MonthlyHoursOverviewReportService extends BaseService
{
    /** @var int */
    private $month = NULL;

    /** @var int */
    private $year = NULL;

    /** @var Attendance */
    private $attendance = NULL;

    /**Store the worker ID whom you need a report */
    private $workerID = [];

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
        $this->attendance = GetAttendanceService::byMonth($this->month, $this->year)->myEmployees()->getAtt();
    }

    /**
     * Create report data
     * 
     * @return MonthlyHoursOverviewReportService
     */
    public function execute(): MonthlyHoursOverviewReportService
    {
        if (!empty($this->workerID)) $this->attendance = $this->attendance->whereIn('worker_id', $this->workerID);
        $output = [];
        /**
         * Day counters (work-home, work-field, SL, ...) count days, not attendance rows.
         * A worker can have several work diaries (= rows) on the same date, so remember
         * which [worker][counter][date] was already counted. Hours are still summed per row.
         */
        $countedDays = [];
        $countDay = function ($workerID, $counter, $date) use (&$output, &$countedDays) {
            if (isset($countedDays[$workerID][$counter][$date])) return;
            $countedDays[$workerID][$counter][$date] = TRUE;
            $output[$workerID][$counter]++;
        };
        try {
            foreach ($this->attendance as $attItem) {
                /**Check if the worker is set in the output array */
                /**If the worker is not set, add the template, and fill basic info */
                if (!isset($output[$attItem->worker_id])) {
                    $output[$attItem->worker_id] = $this->arrayItemTemplate();
                    $workerBasicInfo = Worker::find($attItem->worker_id);
                    $output[$attItem->worker_id]['name'] = $workerBasicInfo->fullName;
                    $output[$attItem->worker_id]['status'] = $workerBasicInfo->status;
                }
                /**Work-hours */
                if ($attItem->work_hours != NULL) {
                    $output[$attItem->worker_id]['work-hours'] += $attItem->work_hours;
                    if ($attItem->type != NULL) $countDay($attItem->worker_id, Types::setByType($attItem->type)->getReportDesc(), $attItem->date);
                }

                /**Absence */
                if ($attItem->absence_reason != NULL) {
                    $countDay($attItem->worker_id, AttendanceAbsenceType::setByType($attItem->absence_reason)->shortDesc(), $attItem->date);
                }
            }


            foreach ($output as $key => $data) {
                /**Total work hours */
                /**TODO: Do this over APP_CONFIG  */
                $sum = $data['work-hours'] + ($data['PL'] * 8) + ($data['HD'] * 8);
                $output[$key]['work-hours-total'] = $sum;

                /**Bonus */
                if ($data[AttendanceAbsenceType::setTypeSickLeave()->shortDesc()] > 0) $output[$key]['bonus'] = FALSE;
            }
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        $this->setData($output);
        return $this;
    }

    /**
     * Returns a array template for what needs to be in the final report per worker.
     * Define here all items and give them default values.
     * 
     * @return array
     */
    private function arrayItemTemplate()
    {
        $output = [
            'name'             => NULL,
            'status'           => NULL,
            'work-hours'       => 0,
            'work-hours-total' => 0,
            'bonus'            => TRUE,
        ];
        /**Add work types */
        foreach (Types::TYPES_FOR_REPORT as $types) {
            $output[$types] = 0;
        }
        /**Add absence types */
        foreach (AttendanceAbsenceType::ABSENCE_TYPE_SHT as $absence) {
            $output[$absence] = 0;
        }
        return $output;
    }

    /**
     * Assign workers for whom you need a report.
     * Pass thru the worker ID. Can be one or many.
     * 
     * @return MonthlyHoursOverviewReportService
     */
    public function setSpecificWorkers(...$workerID)
    {
        $this->workerID = $workerID;
        return $this;
    }
}
