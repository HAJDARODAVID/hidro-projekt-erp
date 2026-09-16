<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use DateTime;
use Illuminate\Support\Collection;
use App\Livewire\LivewireController;
use App\Models\CooperatorsModel;
use App\Models\CooperatorWorkersModel;
use App\Services\Attendance\CreateAttendanceService;
use App\Services\Attendance\DeleteAttendanceService;
use App\Services\WorkdayDiary\GetAllWorkDiariesForDateService;
use App\Livewire\Modules\WorkingHours\Subcontractor as SubcontractorReport;
use App\Services\Attendance\GetSubcontractorWorkerAttendanceByDateService;

/**
 * Modal for adding/removing the attendance of a subcontractor (cooperator) worker on one day.
 * Opened from the subcontractor hours table (see config/global-modal.php):
 *  - by clicking on a date cell of a worker: worker and date are fixed (params: worker, date)
 *  - by clicking on the subcontractor name: the worker is selected from a dropdown with all the
 *    workers of that subcontractor and the date can be changed (params: subcontractor, date)
 * Subcontractor counterpart of the WorkerAttendancePerDay component.
 */
class SubcontractorWorkerAttendancePerDay extends LivewireController
{
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

        $this->resetAttendance()
            ->getWorkerInfo()
            ->getWorkersOptionsItems()
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
            if (!$this->attendance['worker_id']) {
                return $this->notifyMe(translator('Worker is required!'), 'danger');
            }
            if (!$this->attendance['date']) {
                return $this->notifyMe(translator('Date is required!'), 'danger');
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
     * Get all the attendance of the selected worker for the selected day.
     */
    protected function getWorkerAttendance()
    {
        $this->attCollection = new Collection();
        if (empty($this->attendance['worker_id']) || empty($this->attendance['date'])) return $this;

        $service = new GetSubcontractorWorkerAttendanceByDateService(new DateTime($this->attendance['date']), (int) $this->attendance['worker_id']);
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
     *
     * @return void
     */
    public function updatedAttendance($value, $key)
    {
        if (in_array($key, ['worker_id', 'working_day_record_id']) && ($value === 'init-option' || $value === '')) {
            $this->attendance[$key] = null;
        }
        if ($key == 'date') {
            $this->attendance['working_day_record_id'] = null;
            $this->getWorkDiariesOptionsItems();
        }
    }

    public function render()
    {
        $this->getWorkerAttendance();

        return view('livewire.modules.working-hours.components.subcontractor-worker-attendance-per-day', [
            'attCollection' => $this->attCollection
        ]);
    }
}
