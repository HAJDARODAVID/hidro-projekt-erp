<?php

namespace App\Services\Subcontractors;

use App\Services\BaseService;
use App\Models\AppParametersModel;
use App\Models\AttendanceCoOpModel;

/**
 * Class GetSubcontractorsMonthlyHoursReportService.
 *
 * Collects the work hours of all the subcontractor (cooperator) workers for the given month/year,
 * grouped by the subcontractor they belong to.
 * Only the workers that have hours (greater than 0) in the given month are in the output.
 */
class GetSubcontractorsMonthlyHoursReportService extends BaseService
{
    /**Short name of the app parameter that holds the base cost of a subcontractor work hour */
    const BASE_WORK_HOUR_COST_PARAM = 'bwh-c-o';

    /** @var int */
    protected $month;

    /** @var int */
    protected $year;

    /**
     * This will be filled with the attendance data.
     *
     * @var Collation|AttendanceCoOpModel
     */
    private $attendance;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Generate the data that will be displayed in the report.
     * Output structure:
     *  [subcontractorID] => [
     *      'subcontractor-info' => ['name' => string, 'status' => int],
     *      'workers' => [
     *          [workerID] => [
     *              'worker-info' => ['name' => string, 'status' => int],
     *              'attendance'  => ['Y-m-d' => float hours]
     *          ]
     *      ]
     *  ]
     *  ['info']['hour-cost'] => float base cost of one work hour
     *
     * @return array
     */
    public function execute(): array
    {
        $output = [];
        try {
            $this->getAttendanceData();

            foreach ($this->attendance as $att) {
                $worker = $att->getWorkerInfo;
                /**Skip orphan attendance records */
                if ($worker == NULL || $worker->getCoOpInfo == NULL) continue;
                $subID = $worker->cooperator_id;
                $workerID = $att->worker_id;

                /**Set the subcontractor info */
                if (!isset($output[$subID]['subcontractor-info'])) {
                    $output[$subID]['subcontractor-info'] = [
                        'name'   => $worker->getCoOpInfo->name,
                        'status' => $worker->getCoOpInfo->status,
                    ];
                }
                /**Set the worker info */
                if (!isset($output[$subID]['workers'][$workerID]['worker-info'])) {
                    $output[$subID]['workers'][$workerID]['worker-info'] = [
                        'name'   => $worker->fullName,
                        'status' => $worker->status,
                    ];
                }
                /**Sum the hours for the given date */
                if (!isset($output[$subID]['workers'][$workerID]['attendance'][$att->date])) $output[$subID]['workers'][$workerID]['attendance'][$att->date] = 0;
                $output[$subID]['workers'][$workerID]['attendance'][$att->date] += (float) $att->work_hours;
            }

            /**Sort the subcontractors and their workers by name */
            uasort($output, fn($a, $b) => strcmp($a['subcontractor-info']['name'], $b['subcontractor-info']['name']));
            foreach ($output as $subID => $sub) {
                uasort($output[$subID]['workers'], fn($a, $b) => strcmp($a['worker-info']['name'], $b['worker-info']['name']));
            }

            $output['info']['hour-cost'] = $this->getBaseWorkHourCost();

            /**Put the finished data to the payload response */
            $this->setData($output);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }

        return $this->getResponse();
    }

    /**
     * Get all the subcontractor attendance data with hours for the given month/year.
     *
     * @return GetSubcontractorsMonthlyHoursReportService
     */
    private function getAttendanceData()
    {
        $this->attendance = AttendanceCoOpModel::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('work_hours', '>', 0)
            ->with('getWorkerInfo', 'getWorkerInfo.getCoOpInfo')
            ->get();
        return $this;
    }

    /**
     * Get the base cost of one subcontractor work hour from the app parameters.
     * Returns 0 if the parameter is not set.
     *
     * @return float
     */
    private function getBaseWorkHourCost(): float
    {
        $param = AppParametersModel::where('param_name_srt', self::BASE_WORK_HOUR_COST_PARAM)->where('active', TRUE)->first();
        return $param ? (float) $param->value : 0;
    }
}
