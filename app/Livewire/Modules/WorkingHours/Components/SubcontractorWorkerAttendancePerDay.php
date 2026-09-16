<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use DateTime;
use Illuminate\Support\Collection;
use App\Livewire\LivewireController;
use App\Models\CooperatorWorkersModel;
use App\Services\Attendance\CreateAttendanceService;
use App\Services\Attendance\DeleteAttendanceService;
use App\Services\WorkdayDiary\GetAllWorkDiariesForDateService;
use App\Livewire\Modules\WorkingHours\Subcontractor as SubcontractorReport;
use App\Services\Attendance\GetSubcontractorWorkerAttendanceByDateService;

/**
 * Modal for adding/removing the attendance of a subcontractor (cooperator) worker on one day.
 * Opened from the subcontractor hours table by clicking on a date cell (see config/global-modal.php).
 * Subcontractor counterpart of the WorkerAttendancePerDay component.
 */
class SubcontractorWorkerAttendancePerDay extends LivewireController
{
    /**Params passed in from the global modal: ['worker' => cooperator worker ID, 'date' => 'Y-m-d'] */
    public array $params = [];

    /**Set to true when attendance was created or deleted, so the table is only refreshed on close if something changed */
    public bool $hasChanges = false;

    public array $attendance = [];

    /**Display info of the worker ['name' => string, 'subcontractor' => string] */
    public array $workerInfo = [];

    public array $workDiaryOptionsItems = [];

    public null|int|string $hourInput = null;

    private Collection $attCollection;

    public function mount()
    {
        $this->resetAttendance()
            ->getWorkerInfo()
            ->getWorkDiariesOptionsItems();
    }

    /*
    |--------------------------------------------------------------------------
    | Wire click actions
    |--------------------------------------------------------------------------
    */

    /**
     * Action for creating and saving a new attendance entry for the subcontractor worker on the given day.
     * Resets the form and lets the attendance table refresh on the next render.
     */
    public function saveNewAttendanceAction()
    {
        try {
            if (!$this->attendance['worker_id'] || !$this->attendance['date']) {
                return $this->notifyMe(translator('Worker and date are required!'), 'danger');
            }
            if (!is_numeric($this->attendance['work_hours']) || (float) $this->attendance['work_hours'] <= 0) {
                return $this->notifyMe(translator('Work hours are required!'), 'danger');
            }

            $response = CreateAttendanceService::cooperator()
                ->setWorkerID($this->attendance['worker_id'])
                ->setDiaryID($this->attendance['working_day_record_id'] ?: null)
                ->setWorkHours($this->attendance['work_hours'])
                ->setDate($this->attendance['date'])
                ->execute();

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
    | Setter and getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get the worker and subcontractor names formatted for the display.
     *
     * @return self
     */
    private function getWorkerInfo()
    {
        $worker = CooperatorWorkersModel::with('getCoOpInfo')->find($this->params['worker'] ?? null);
        if ($worker) {
            $this->workerInfo['name'] = str_pad($worker->id, 3, '0', STR_PAD_LEFT) . ' | ' . $worker->fullName;
            $this->workerInfo['subcontractor'] = $worker->getCoOpInfo?->name ?? '';
        }
        return $this;
    }

    /**
     * Get options that will be used in the select element.
     * Gets all the work diaries for the given day.
     */
    private function getWorkDiariesOptionsItems()
    {
        $service = new GetAllWorkDiariesForDateService(new DateTime($this->params['date'] ?? null));
        $this->workDiaryOptionsItems = $service->executeForDropdown('with-user')->getResponse()['data'];
        return $this;
    }

    /**
     * Set the default attendance array keys and values.
     */
    protected function resetAttendance()
    {
        $this->attendance = [
            'worker_id' => $this->params['worker'] ?? null,
            'working_day_record_id' => null,
            'work_hours' => null,
            'date' => $this->params['date'] ?? null,
        ];
        $this->hourInput = null;
        return $this;
    }

    /**
     * Get all the attendance of the worker for the given day.
     */
    protected function getWorkerAttendance()
    {
        $service = new GetSubcontractorWorkerAttendanceByDateService(new DateTime($this->params['date'] ?? null), $this->params['worker'] ?? null);
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

    public function render()
    {
        $this->getWorkerAttendance();

        return view('livewire.modules.working-hours.components.subcontractor-worker-attendance-per-day', [
            'attCollection' => $this->attCollection
        ]);
    }
}
