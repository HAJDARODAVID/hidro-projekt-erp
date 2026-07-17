<?php

namespace App\Services\Attendance;

use App\Models\Employees\Attendance;
use App\Models\WorkDiary\WorkDiary;
use App\Services\BaseService;
use DateTime;
use Illuminate\Support\Collection;

/**
 * Class GetWorkerAttendanceByDateService.
 */
class GetWorkerAttendanceByDateService extends BaseService
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
     * This will get all the attendance records for the worker on the given date,
     * each formatted as an AttendanceDayDto.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $attendance = Attendance::where('worker_id', $this->workerId)
                ->where('date', $this->date->format('Y-m-d'))
                ->get();

            $workDiaries = WorkDiary::with(['user', 'constructionSite'])
                ->whereIn('id', $attendance->pluck('working_day_record_id'))
                ->get()
                ->keyBy('id');

            $output = new Collection();
            foreach ($attendance as $att) {
                $workDiary = $workDiaries->get($att->working_day_record_id);

                $dto = (new AttendanceDayDto())
                    ->setId($att->id)
                    ->setConstructionSiteName($workDiary ? $workDiary->user->name . ' | ' . $workDiary->constructionSite->name : null)
                    ->setWorkingHours($att->work_hours)
                    ->setAbsenceReason($att->absence_reason)
                    ->setWorkType($att->type);

                $output->push($dto);
            }

            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }

        return $this;
    }
}
