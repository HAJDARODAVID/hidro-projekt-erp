<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Models\Employees\Worker;
use Illuminate\Support\Collection;
use App\Services\Attendance\AbsenceBtnObject;
use App\Services\Attendance\CreateAttendanceService;
use App\Services\Attendance\GetWorkerAttendanceByDateService;
use App\Services\WorkdayDiary\GetAllWorkDiariesForDateService;
use DateTime;

class WorkerAttendancePerDay extends LivewireController
{
    public array $params = [];

    public array $attendance = [];
    public array $workerInfo = [];

    public array $workDiaryOptionsItems = [];

    public null|int|string $hourInput;
    public array $hourInputAtt;

    private Collection $attCollection;

    public function mount()
    {
        $this->resetAttendance()
            ->setHoursInputAtt()
            ->getWorkerName()
            ->getWorkDiariesOptionsItems();
    }

    /*
    |--------------------------------------------------------------------------
    | Wire click actions
    |--------------------------------------------------------------------------
    */

    /**
     * Action for selecting a absence type.
     * Defines a custom style for the input and add a value to the properties.
     * 
     * @param int $type Type of absence
     */
    public function selectAbsenceReasonAction($type)
    {
        $this->hourInput = AttendanceAbsenceType::setByType($type)->shortDesc();
        $this->setHoursInputAbsenceAtt($type)->setAttendanceAbsence($type);
    }

    /**
     * Removing selected absence type action.
     * This will remove and reset the $hourInput property.
     */
    public function resetHourInputAction()
    {
        $this->hourInput = null;
        $this->setHoursInputAtt()->setAttendanceAbsence(null);
    }

    /**
     * Action for creating and saving a new attendance entry for the worker on the given day.
     * Resets the form and lets the attendance table refresh on the next render.
     */
    public function saveNewAttendanceAction()
    {
        try {
            if (!$this->attendance['worker_id'] || (!$this->attendance['work_hours'] && !$this->attendance['absence_reason'])) {
                return $this->notifyMe(translator('Work hours or absence reason are required!'), 'danger');
            }

            $response = CreateAttendanceService::myWorker()
                ->setWorkerID($this->attendance['worker_id'])
                ->setDiaryID($this->attendance['working_day_record_id'])
                ->setType($this->attendance['type'])
                ->setWorkHours($this->attendance['work_hours'])
                ->setAbsenceReason($this->attendance['absence_reason'])
                ->setDate($this->attendance['date'])
                ->execute();

            if (is_array($response) && isset($response['success']) && $response['success'] === false) {
                return $this->notifyMe($response['error'] ?? translator('Failed to save attendance!'), 'danger');
            }

            $this->resetAttendance();
            return $this->notifyMe(translator('Attendance entry created!'));
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Setter and getters
    |--------------------------------------------------------------------------
    */

    /**
     * Set attributes that will be used in the $hourInput property.
     * Defines all the styles for when a hour option is active
     */
    private function setHoursInputAtt()
    {
        $this->hourInputAtt = [
            'type' => 'number',
            'disabled' => false,
            'delete' => false,
            'style' => [],
        ];
        return $this;
    }

    /**
     * Set attributes that will be used in the $hourInput property.
     * Defines all the styles for when absence is selected.
     */
    private function setHoursInputAbsenceAtt(int $type)
    {
        $this->hourInputAtt = [
            'type' => 'text',
            'disabled' => true,
            'delete' => true,
            'style' => [
                'background-color' => AbsenceBtnObject::getBackgroundColorForType(AttendanceAbsenceType::setByType($type))
            ],
        ];
        return $this;
    }

    /**
     * Set the absence type in the attendance property
     * 
     * @return self
     */
    private function setAttendanceAbsence($type)
    {
        $this->attendance['absence_reason'] = $type;
        $this->attendance['work_hours'] = null;
        return $this;
    }

    /**
     * Get the workers name and formate it for the display
     * 
     * @return self
     */
    private function getWorkerName()
    {
        $worker = Worker::find($this->params['worker'] ?? null);
        if ($worker) $this->workerInfo['name'] = str_pad($worker->id, 3, '0', STR_PAD_LEFT) . ' | ' . $worker->fullName;
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
     * Set the default attendance array key's and values.
     */
    protected function resetAttendance()
    {
        $this->attendance = [
            'worker_id' => $this->params['worker'] ?? null,
            'working_day_record_id' => null,
            'type' => null,
            'work_hours' => null,
            'absence_reason' => null,
            'date' => $this->params['date'] ?? null,
            'comment' => null,
        ];
        $this->resetHourInputAction();
        return $this;
    }

    /**
     * Set the default attendance array key's and values.
     */
    protected function getWorkerAttendance()
    {
        $service = new GetWorkerAttendanceByDateService(new DateTime($this->params['date'] ?? null), $this->params['worker'] ?? null);
        $this->attCollection = $service->execute()->getResponse()['data'];
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

        return view('livewire.modules.working-hours.components.worker-attendance-per-day', [
            'attCollection' => $this->attCollection
        ]);
    }
}
