<?php

namespace App\Services\Subcontractors;

use App\Services\BaseService;
use App\Models\CooperatorsModel;
use App\Models\AppParametersModel;
use App\Models\AttendanceCoOpModel;
use App\Models\CooperatorWorkersModel;

/**
 * Class GetSubcontractorsMonthlyHoursReportService.
 *
 * Collects the work hours of all the subcontractor (cooperator) workers for the given month/year,
 * grouped by the subcontractor they belong to.
 * Only the workers that have hours (greater than 0) in the given month are in the output.
 * Exception: for the current month/year, or a month without any attendance data,
 * all the active subcontractors with their active workers are in the output as well.
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

            /**
             * If you are getting data from the current year and month, or a month without any attendance data,
             * put all the active subcontractors with their active workers in the output.
             * Past months with attendance data show only the subcontractors with data.
             * Inactive subcontractors with attendance data are already in the output.
             */
            $isCurrentMonth = $this->year == now()->format('Y') && $this->month == now()->format('n');
            if ($isCurrentMonth || empty($output)) {
                $this->addActiveSubcontractors($output);
            }

            /**Sort the subcontractors and their workers by name */
            uasort($output, fn($a, $b) => strcmp($a['subcontractor-info']['name'], $b['subcontractor-info']['name']));
            foreach ($output as $subID => $sub) {
                uasort($output[$subID]['workers'], fn($a, $b) => strcmp($a['worker-info']['name'], $b['worker-info']['name']));
            }

            $output['info']['hour-cost'] = self::getBaseWorkHourCost();

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
     * Add all the active subcontractors and their active workers to the output, if they are not in it already.
     * Workers added here have no attendance data.
     *
     * @param array $output The report output (passed by reference)
     * @return GetSubcontractorsMonthlyHoursReportService
     */
    private function addActiveSubcontractors(array &$output)
    {
        $subcontractors = CooperatorsModel::where('status', CooperatorsModel::COOPERATORS_STATUS_ACTIVE)
            ->with(['getAllWorkers' => fn($q) => $q->where('status', CooperatorWorkersModel::COOPERATORS_WORKER_STATUS_ACTIVE)])
            ->get();

        foreach ($subcontractors as $subcontractor) {
            if (!isset($output[$subcontractor->id]['subcontractor-info'])) {
                $output[$subcontractor->id]['subcontractor-info'] = [
                    'name'   => $subcontractor->name,
                    'status' => $subcontractor->status,
                ];
            }
            if (!isset($output[$subcontractor->id]['workers'])) $output[$subcontractor->id]['workers'] = [];

            foreach ($subcontractor->getAllWorkers as $worker) {
                if (isset($output[$subcontractor->id]['workers'][$worker->id]['worker-info'])) continue;
                $output[$subcontractor->id]['workers'][$worker->id] = [
                    'worker-info' => [
                        'name'   => $worker->fullName,
                        'status' => $worker->status,
                    ],
                    'attendance' => [],
                ];
            }
        }
        return $this;
    }

    /**
     * Get the base cost of one subcontractor work hour from the app parameters.
     * Returns 0 if the parameter is not set.
     *
     * @return float
     */
    public static function getBaseWorkHourCost(): float
    {
        $param = AppParametersModel::where('param_name_srt', self::BASE_WORK_HOUR_COST_PARAM)->where('active', TRUE)->first();
        return $param ? (float) $param->value : 0;
    }
}
