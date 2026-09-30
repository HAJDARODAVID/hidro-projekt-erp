<?php

namespace App\Services\ConstructionSite;

use App\Services\BaseService;
use App\Models\WorkingDayLogModel;

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

        $output = [];
        foreach ($logs as $log) {
            $wdr  = $log->getWorkingDayRecord;
            $user = $wdr?->getUser;

            $output[] = [
                'date'                  => $wdr?->date ?? $log->created_at?->format('Y-m-d'),
                'working_day_record_id' => $log->working_day_record_id,
                'worker'                => $user?->getWorker?->fullName ?? $user?->name,
                'log'                   => $log->log,
            ];
        }

        return $this->setData($output);
    }
}
