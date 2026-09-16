<?php

namespace App\Services\Subcontractors;

use App\Services\BaseService;
use App\Models\CooperatorsModel;
use App\Models\AttendanceCoOpModel;
use App\Models\WorkingDayRecordModel;

/**
 * Class GetSubcontractorHoursExportDataService.
 *
 * Prepares the subcontractor work hours of a month/year for the excel export.
 * Pass a subcontractor ID to export a single subcontractor, or NULL for all of them.
 */
class GetSubcontractorHoursExportDataService extends BaseService
{
    /** @var int */
    protected $month;

    /** @var int */
    protected $year;

    /** @var int|NULL */
    protected $subcontractorID;

    /**
     * This will be filled with the attendance data.
     *
     * @var Collation|AttendanceCoOpModel
     */
    private $attendance;

    public function __construct(int $month, int $year, ?int $subcontractorID = NULL)
    {
        $this->month = $month;
        $this->year = $year;
        $this->subcontractorID = $subcontractorID;
    }

    /**
     * Generate the data for the export.
     * Output structure:
     *  'subcontractor' => ['id' => int, 'name' => string] | NULL when exporting all
     *  'hour-cost'     => float base cost of one work hour
     *  'summary'       => [['id', 'name', 'subcontractor', 'hours', 'cost'], ...] one row per worker
     *  'list'          => [['date', 'id', 'name', 'subcontractor', 'construction-site', 'hours'], ...] one row per attendance record
     *
     * @return GetSubcontractorHoursExportDataService
     */
    public function execute(): self
    {
        try {
            $subcontractor = $this->getSubcontractor();
            $this->getAttendanceData();

            if ($this->attendance->isEmpty()) {
                throw new \Exception(translator('No subcontractor hours for the selected month!'));
            }

            $this->setData([
                'subcontractor' => $subcontractor,
                'hour-cost'     => GetSubcontractorsMonthlyHoursReportService::getBaseWorkHourCost(),
                'summary'       => $this->buildSummary(),
                'list'          => $this->buildList(),
            ]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }

        return $this;
    }

    /**
     * Validate and get the subcontractor info, if a ID is given.
     *
     * @return array|NULL
     */
    private function getSubcontractor()
    {
        if ($this->subcontractorID === NULL) return NULL;
        $subcontractor = CooperatorsModel::find($this->subcontractorID);
        if ($subcontractor == NULL) throw new \Exception(translator('Subcontractor not found!'));
        return ['id' => $subcontractor->id, 'name' => $subcontractor->name];
    }

    /**
     * Get all the subcontractor attendance data with hours for the given month/year.
     * Orphan records (no worker or no subcontractor) are skipped.
     *
     * @return GetSubcontractorHoursExportDataService
     */
    private function getAttendanceData()
    {
        $attendance = AttendanceCoOpModel::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('work_hours', '>', 0)
            ->with('getWorkerInfo', 'getWorkerInfo.getCoOpInfo', 'getWorkingDayRecord', 'getWorkingDayRecord.getConstructionSite')
            ->orderBy('date', 'ASC')
            ->orderBy('worker_id', 'ASC');

        if ($this->subcontractorID !== NULL) {
            $attendance = $attendance->whereHas('getWorkerInfo', fn($q) => $q->where('cooperator_id', $this->subcontractorID));
        }

        $this->attendance = $attendance->get()->filter(fn($att) => $att->getWorkerInfo != NULL && $att->getWorkerInfo->getCoOpInfo != NULL);
        return $this;
    }

    /**
     * One row per worker with the sum of the hours and the cost.
     *
     * @return array
     */
    private function buildSummary(): array
    {
        $hourCost = GetSubcontractorsMonthlyHoursReportService::getBaseWorkHourCost();
        $output = [];
        foreach ($this->attendance as $att) {
            $workerID = $att->worker_id;
            if (!isset($output[$workerID])) {
                $output[$workerID] = [
                    'id'            => $workerID,
                    'name'          => $att->getWorkerInfo->fullName,
                    'subcontractor' => $att->getWorkerInfo->getCoOpInfo->name,
                    'hours'         => 0,
                    'cost'          => 0,
                ];
            }
            $output[$workerID]['hours'] += (float) $att->work_hours;
        }
        foreach ($output as $workerID => $row) {
            $output[$workerID]['cost'] = round($row['hours'] * $hourCost, 2);
        }
        usort($output, fn($a, $b) => [$a['subcontractor'], $a['name']] <=> [$b['subcontractor'], $b['name']]);
        return $output;
    }

    /**
     * One row per attendance record with the construction site.
     *
     * @return array
     */
    private function buildList(): array
    {
        $output = [];
        foreach ($this->attendance as $att) {
            $output[] = [
                'date'              => $att->date,
                'id'                => $att->worker_id,
                'name'              => $att->getWorkerInfo->fullName,
                'subcontractor'     => $att->getWorkerInfo->getCoOpInfo->name,
                'construction-site' => $this->getConstructionSiteName($att),
                'hours'             => (float) $att->work_hours,
            ];
        }
        return $output;
    }

    /**
     * Resolve the construction site name of a attendance record.
     * The misc work list (see WorkingDayRecordModel) has priority over the working day record.
     *
     * @return string|NULL
     */
    private function getConstructionSiteName(AttendanceCoOpModel $att)
    {
        if (isset(WorkingDayRecordModel::MISC_WORK_LIST[$att->working_day_record_id])) {
            return WorkingDayRecordModel::MISC_WORK_LIST[$att->working_day_record_id];
        }
        return $att->getWorkingDayRecord?->getConstructionSite?->name;
    }
}
