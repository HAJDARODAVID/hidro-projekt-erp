<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Collection;
use App\Livewire\LivewireController;
use App\Models\CooperatorsModel;
use App\Models\AttendanceCoOpModel;
use App\Models\CooperatorWorkersModel;
use App\Services\Attendance\CreateAttendanceService;
use App\Services\Attendance\DeleteAttendanceService;
use App\Services\WorkdayDiary\GetAllWorkDiariesForDateService;
use App\Livewire\Modules\WorkingHours\Subcontractor as SubcontractorReport;
use App\Services\Attendance\GetSubcontractorWorkerAttendanceByDateService;

/**
 * Modal for adding/removing the attendance of a subcontractor (cooperator) worker.
 * Opened from the subcontractor hours table (see config/global-modal.php):
 *  - by clicking on a date cell of a worker: worker and date are fixed (params: worker, date)
 *  - by clicking on the subcontractor name: the worker is selected from a dropdown with all the
 *    workers of that subcontractor, and the attendance can be added for a single day or a date
 *    range (params: subcontractor, date). A range creates one entry per day, without a workday
 *    diary (diaries belong to a single date), and skips the days that already have attendance.
 * Subcontractor counterpart of the WorkerAttendancePerDay component.
 */
class SubcontractorWorkerAttendancePerDay extends LivewireController
{
    /**Max number of days in a date range */
    const MAX_RANGE_DAYS = 62;

    /**
     * Params passed in from the global modal:
     * ['worker' => cooperator worker ID, 'date' => 'Y-m-d'] or
     * ['subcontractor' => cooperator ID, 'date' => 'Y-m-d']
     */
    public array $params = [];

    /**Set to true when attendance was created or deleted, so the table is only refreshed on close if something changed */
    public bool $hasChanges = false;

    /**TRUE when the worker is chosen over the dropdown (opened from the subcontractor name) */
    public bool $selectWorker = false;

    /**End of the date range (select worker mode). Equal to attendance.date for a single day. */
    public string|null $dateTo = null;

    /**Skip Saturdays and Sundays when saving a date range */
    public bool $skipWeekends = true;

    public array $attendance = [];

    /**Display info of the worker ['name' => string, 'subcontractor' => string] */
    public array $workerInfo = [];

    /**Options for the worker select [id => name], all the workers of the subcontractor */
    public array $workersOptionsItems = [];

    public array $workDiaryOptionsItems = [];

    public null|int|string $hourInput = null;

    private Collection $attCollection;

    public function mount()
    {
        $this->selectWorker = empty($this->params['worker']) && !empty($this->params['subcontractor']);

        $this->resetAttendance();
        $this->dateTo = $this->attendance['date'];

        $this->getWorkerInfo()
            ->getWorkersOptionsItems()
            ->getWorkDiariesOptionsItems();
    }

    /*
    |--------------------------------------------------------------------------
    | Wire click actions
    |--------------------------------------------------------------------------
    */

    /**
     * Action for creating and saving the attendance for the selected worker.
     * Single day: one entry with the selected workday diary.
     * Date range: one entry per day without a diary, days with existing attendance are skipped.
     * Resets the form and lets the attendance table refresh on the next render.
     */
    public function saveNewAttendanceAction()
    {
        try {
            if (!$this->attendance['worker_id']) {
                return $this->notifyMe(translator('Worker is required!'), 'danger');
            }
            if (!$this->attendance['date']) {
                return $this->notifyMe(translator('Date is required!'), 'danger');
            }
            if (!is_numeric($this->attendance['work_hours']) || (float) $this->attendance['work_hours'] <= 0) {
                return $this->notifyMe(translator('Work hours are required!'), 'danger');
            }

            if ($this->isRange()) return $this->saveRange();

            $response = $this->createAttendance($this->attendance['date'], $this->attendance['working_day_record_id'] ?: null);
            if (is_array($response) && isset($response['success']) && $response['success'] === false) {
                return $this->notifyMe($response['error'] ?? translator('Failed to save attendance!'), 'danger');
            }

            $this->hasChanges = true;
            $this->resetAttendance();
            return $this->notifyMe(translator('Attendance entry created!'));
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
    }

    /**
     * Save one attendance entry per day of the selected range.
     * Weekends are skipped when the switch is on, days that already have attendance are always skipped.
     */
    private function saveRange()
    {
        $dates = $this->getRangeDates();
        if (count($dates) > self::MAX_RANGE_DAYS) {
            return $this->notifyMe(translator('The date range is too long!') . ' (max. ' . self::MAX_RANGE_DAYS . ' ' . translator('days') . ')', 'danger');
        }
        if (empty($dates)) {
            return $this->notifyMe(translator('There are no days to save in the selected range!'), 'warning');
        }

        $existing = AttendanceCoOpModel::where('worker_id', $this->attendance['worker_id'])
            ->whereIn('date', $dates)
            ->pluck('date')
            ->map(fn($d) => (new DateTime($d))->format('Y-m-d'))
            ->unique()
            ->all();

        $created = 0;
        $failed = [];
        foreach ($dates as $date) {
            if (in_array($date, $existing)) continue;
            $response = $this->createAttendance($date, null);
            if (is_array($response) && isset($response['success']) && $response['success'] === false) {
                $failed[] = $date;
                continue;
            }
            $created++;
        }

        if ($created > 0) $this->hasChanges = true;
        $this->resetAttendance();

        $message = translator('Attendance entries created') . ': ' . $created;
        if (!empty($existing)) $message .= ' | ' . translator('Skipped (attendance exists)') . ': ' . implode(', ', $existing);
        if (!empty($failed)) $message .= ' | ' . translator('Failed') . ': ' . implode(', ', $failed);

        return $this->notifyMe($message, empty($failed) ? ($created > 0 ? 'success' : 'warning') : 'danger');
    }

    /**
     * Create one attendance entry over the CreateAttendanceService.
     *
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function createAttendance(string $date, int|null $diaryID)
    {
        return CreateAttendanceService::cooperator()
            ->setWorkerID($this->attendance['worker_id'])
            ->setDiaryID($diaryID)
            ->setWorkHours($this->attendance['work_hours'])
            ->setDate($date)
            ->execute();
    }

    /**
     * Action for deleting an existing attendance entry.
     *
     * @param int $id Attendance record ID
     */
    public function deleteAttendanceAction($id)
    {
        try {
            $response = DeleteAttendanceService::byID($id, 'co-op')->execute()->getResponse();

            if (!$response['success']) {
                return $this->notifyMe($response['message'], 'danger');
            }

            $this->hasChanges = true;
            return $this->notifyMe($response['message']);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
    }

    /**
     * Called by the global-modal component (see config/global-modal.php)
     * before the modal closes.
     * Refreshes the subcontractor hours report, which re-mounts the
     * SubcontractorTable with fresh data, but only if attendance was created or
     * deleted in this modal ($hasChanges), so closing without changes
     * does not trigger a needless reload of the table.
     */
    public function beforeCloseAction()
    {
        if (!$this->hasChanges) return;

        $this->hasChanges = false;
        $this->dispatch('refresh-subcontractor-hours-report')->to(SubcontractorReport::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Date range helpers
    |--------------------------------------------------------------------------
    */

    /**
     * TRUE when a date range (more than one day) is selected in the select worker mode.
     */
    public function isRange(): bool
    {
        return $this->selectWorker
            && !empty($this->attendance['date'])
            && !empty($this->dateTo)
            && $this->dateTo > $this->attendance['date'];
    }

    /**
     * All the dates (Y-m-d) of the selected range, without the weekends if the switch is on.
     *
     * @return array
     */
    private function getRangeDates(): array
    {
        $from = new DateTime($this->attendance['date']);
        $to = (new DateTime($this->dateTo))->modify('+1 day');

        $output = [];
        foreach (new DatePeriod($from, new DateInterval('P1D'), $to) as $day) {
            if ($this->skipWeekends && $day->format('N') > 5) continue;
            $output[] = $day->format('Y-m-d');
        }
        return $output;
    }

    /*
    |--------------------------------------------------------------------------
    | Setter and getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get the worker and subcontractor names formatted for the display.
     * In the select worker mode only the subcontractor name is set.
     *
     * @return self
     */
    private function getWorkerInfo()
    {
        if ($this->selectWorker) {
            $subcontractor = CooperatorsModel::find($this->params['subcontractor']);
            $this->workerInfo['subcontractor'] = $subcontractor?->name ?? '';
            return $this;
        }

        $worker = CooperatorWorkersModel::with('getCoOpInfo')->find($this->params['worker'] ?? null);
        if ($worker) {
            $this->workerInfo['name'] = str_pad($worker->id, 3, '0', STR_PAD_LEFT) . ' | ' . $worker->fullName;
            $this->workerInfo['subcontractor'] = $worker->getCoOpInfo?->name ?? '';
        }
        return $this;
    }

    /**
     * Get options that will be used in the worker select element.
     * All the workers of the subcontractor, active ones first.
     */
    private function getWorkersOptionsItems()
    {
        if (!$this->selectWorker) return $this;

        $workers = CooperatorWorkersModel::where('cooperator_id', $this->params['subcontractor'])
            ->orderBy('status', 'DESC')
            ->orderBy('lastName')
            ->orderBy('firstName')
            ->get();

        $this->workersOptionsItems = [];
        foreach ($workers as $worker) {
            $name = str_pad($worker->id, 3, '0', STR_PAD_LEFT) . ' | ' . $worker->fullName;
            if ($worker->status != CooperatorWorkersModel::COOPERATORS_WORKER_STATUS_ACTIVE) $name .= ' (' . translator('inactive') . ')';
            $this->workersOptionsItems[$worker->id] = $name;
        }
        return $this;
    }

    /**
     * Get options that will be used in the select element.
     * Gets all the work diaries for the selected day.
     */
    private function getWorkDiariesOptionsItems()
    {
        $this->workDiaryOptionsItems = [];
        if (empty($this->attendance['date'])) return $this;

        $service = new GetAllWorkDiariesForDateService(new DateTime($this->attendance['date']));
        $this->workDiaryOptionsItems = $service->executeForDropdown('with-user')->getResponse()['data'];
        return $this;
    }

    /**
     * Set the default attendance array keys and values.
     * The selected worker and date are kept, so more entries can be added in a row.
     */
    protected function resetAttendance()
    {
        $this->attendance = [
            'worker_id' => $this->attendance['worker_id'] ?? $this->params['worker'] ?? null,
            'working_day_record_id' => null,
            'work_hours' => null,
            'date' => $this->attendance['date'] ?? $this->params['date'] ?? date('Y-m-d'),
        ];
        $this->hourInput = null;
        return $this;
    }

    /**
     * Get all the attendance of the selected worker for the selected day or date range.
     */
    protected function getWorkerAttendance()
    {
        $this->attCollection = new Collection();
        if (empty($this->attendance['worker_id']) || empty($this->attendance['date'])) return $this;

        $service = new GetSubcontractorWorkerAttendanceByDateService(
            new DateTime($this->attendance['date']),
            (int) $this->attendance['worker_id'],
            $this->isRange() ? new DateTime($this->dateTo) : null
        );
        $response = $service->execute()->getResponse();
        if (!$response['success']) $this->showException($response['message']);
        $this->attCollection = $response['success'] ? $response['data'] : new Collection();
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Properties updates
    |--------------------------------------------------------------------------
    */

    /**
     * Add the hours to the attendance property after the hourInput updated
     *
     * @return void
     */
    public function updatedHourInput(null|int|string $value)
    {
        $this->attendance['work_hours'] = $value;
    }

    /**
     * Normalize the select placeholder option (see components.ui.v2.select) to NULL
     * and reload the work diaries when the date is changed (select worker mode).
     * The range end is pulled along when the start date is moved past it.
     *
     * @return void
     */
    public function updatedAttendance($value, $key)
    {
        if (in_array($key, ['worker_id', 'working_day_record_id']) && ($value === 'init-option' || $value === '')) {
            $this->attendance[$key] = null;
        }
        if ($key == 'date') {
            if (empty($this->dateTo) || $this->dateTo < $value) $this->dateTo = $value;
            $this->attendance['working_day_record_id'] = null;
            $this->getWorkDiariesOptionsItems();
        }
    }

    /**
     * Keep the range end on or after the start date, and drop the diary for a range.
     *
     * @return void
     */
    public function updatedDateTo($value)
    {
        if (empty($value) || $value < $this->attendance['date']) $this->dateTo = $this->attendance['date'];
        if ($this->isRange()) $this->attendance['working_day_record_id'] = null;
    }

    public function render()
    {
        $this->getWorkerAttendance();

        return view('livewire.modules.working-hours.components.subcontractor-worker-attendance-per-day', [
            'attCollection' => $this->attCollection,
            'isRange' => $this->isRange(),
        ]);
    }
}
