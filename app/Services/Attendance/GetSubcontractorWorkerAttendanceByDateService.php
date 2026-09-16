<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCoOpModel;
use App\Models\WorkDiary\WorkDiary;
use App\Services\BaseService;
use DateTime;
use Illuminate\Support\Collection;

/**
 * Class GetSubcontractorWorkerAttendanceByDateService.
 *
 * Subcontractor (cooperator) worker counterpart of the GetWorkerAttendanceByDateService.
 */
class GetSubcontractorWorkerAttendanceByDateService extends BaseService
{
    private DateTime $date;

    private int|null $workerId;

    public function __construct(DateTime $date, int|null $workerId)
    {
        $this->date = $date;
        $this->workerId = $workerId;
    }

    /**
     * Execute the service.
     * This will get all the attendance records for the subcontractor worker on the given date,
     * each formatted as an AttendanceDayDto.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $attendance = AttendanceCoOpModel::where('worker_id', $this->workerId)
                ->where('date', $this->date->format('Y-m-d'))
                ->orderBy('id')
                ->get();

            $workDiaries = WorkDiary::with(['user', 'constructionSite'])
                ->whereIn('id', $attendance->pluck('working_day_record_id')->filter())
                ->get()
                ->keyBy('id');

            $output = new Collection();
            foreach ($attendance as $att) {
                $workDiary = $workDiaries->get($att->working_day_record_id);

                $dto = (new AttendanceDayDto())
                    ->setId($att->id)
                    ->setConstructionSiteName($workDiary ? ($workDiary->user?->name ?? '-') . ' | ' . ($workDiary->constructionSite?->name ?? translator('No construction site')) : null)
                    ->setWorkingHours($att->work_hours);

                $output->push($dto);
            }

            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }

        return $this;
    }
}
