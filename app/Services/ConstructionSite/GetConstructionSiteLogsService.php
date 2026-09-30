<?php

namespace App\Services\ConstructionSite;

use App\Services\BaseService;
use App\Models\WorkingDayLogModel;
use App\Models\AttendanceCoOpModel;
use App\Models\Employees\Attendance;

/**
 * Class GetConstructionSiteLogsService.
 * Gathers all the working day logs for a specific construction site.
 */
class GetConstructionSiteLogsService extends BaseService
{
    /** @var int */
    private int $constructionSiteId;

    public function __construct(int $constructionSiteId)
    {
        $this->constructionSiteId = $constructionSiteId;
    }

    /**
     * Execute the service
     */
    public function execute(): self
    {
        $logs = WorkingDayLogModel::where('construction_site_id', $this->constructionSiteId)
            ->with('getWorkingDayRecord.getUser.getWorker')
            ->orderBy('id', 'desc')
            ->get();

        //Sum the work hours per work diary
        $wdrIds = $logs->pluck('working_day_record_id')->filter()->unique()->values();
        $companyHours = $this->sumHoursPerWorkDiary(Attendance::query(), $wdrIds);
        $contractorsHours = $this->sumHoursPerWorkDiary(AttendanceCoOpModel::query(), $wdrIds);

        $output = [];
        //Work diaries whose hours were already added, so a diary with multiple logs isn't counted twice
        $countedWdrIds = [];
        foreach ($logs as $log) {
            $wdr   = $log->getWorkingDayRecord;
            $user  = $wdr?->getUser;
            $wdrId = $log->working_day_record_id;

            $showHours = $wdrId && !in_array($wdrId, $countedWdrIds);
            if ($showHours) $countedWdrIds[] = $wdrId;

            $company     = $showHours ? (float) ($companyHours[$wdrId] ?? 0) : NULL;
            $contractors = $showHours ? (float) ($contractorsHours[$wdrId] ?? 0) : NULL;

            $output[] = [
                'date'                  => $wdr?->date ?? $log->created_at?->format('Y-m-d'),
                'working_day_record_id' => $wdrId,
                'worker'                => $user?->getWorker?->fullName ?? $user?->name,
                'log'                   => $log->log,
                'company_hours'         => $company,
                'contractors_hours'     => $contractors,
                'total_hours'           => $showHours ? $company + $contractors : NULL,
            ];
        }

        return $this->setData($output);
    }

    /**
     * Returns the summed work hours keyed by the working_day_record_id.
     */
    private function sumHoursPerWorkDiary($query, $wdrIds)
    {
        return $query->whereIn('working_day_record_id', $wdrIds)
            ->groupBy('working_day_record_id')
            ->selectRaw('working_day_record_id, SUM(work_hours) as hours')
            ->pluck('hours', 'working_day_record_id');
    }
}
