<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\AbsenceBtnObject;
use App\Services\Attendance\CreateAttendanceService;
use App\Services\Attendance\GetAllAttendanceEligibleWorkersService;
use App\Services\WorkdayDiary\GetAllWorkDiariesForDateService;
use App\Services\WorkdayDiary\Types;
use DateTime;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class NewAttendanceModal extends LivewireController
{
    public array $attendance = [];

    public string|null $date = null;
    public array $dateInputAtt = [];

    public null|int|string $hourInput;
    public array $hourInputAtt;

    public array $workDiaryOptionsItems = [];

    public int|null $worker = null;
    public array $workersOptionsItems = [];
    public array $workersSelectAtt = [];

    public array $workDayTypeItems = [];

    /*
    |--------------------------------------------------------------------------
    | Component initializing stage
    |--------------------------------------------------------------------------
    */

    public function mount()
    {
        $this->setHoursInputAtt();
        //$this->openModal();
    }

    #[On('open-new-attendance-modal')]
    public function initializeModal($params = [])
    {
        try {
            $this->resetAttendance()
                ->setHoursInputAtt()
                ->setWorkersOptionsItems()
                ->setFromParams($params)
                ->setWorkDiariesOptionsItems();
            $this->openModal();
        } catch (\Throwable $th) {
            return $this->dispatch('show-exception-modal', $th->getMessage());
        }
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
     * Action for creating and saving a new attendance entry.
     */
    public function saveAttendanceAction()
    {
        try {
            if (
                !$this->attendance['date']
                || !$this->attendance['worker_id']
                || (!$this->attendance['work_hours'] && !$this->attendance['absence_reason'])
            ) {
                return $this->notifyMe(translator('Date, worker and work hours or absence reason are required!'), 'danger');
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

            $this->closeModal();
            $this->dispatch('refresh-attendance-data')->to(MonthlyOverviewCard::class);
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
     * Set the default attendance array key's and values.
     */
    protected function resetAttendance()
    {
        $this->date = Carbon::today()->toDateString();
        $this->attendance = [
            'worker_id' => null,
            'working_day_record_id' => null,
            'type' => null,
            'work_hours' => null,
            'absence_reason' => null,
            'date' => $this->date,
        ];
        return $this;
    }

    /**
     * Set options that will be used in the select element.
     * Gets all the workers that can be in attendance.
     */
    private function setWorkersOptionsItems()
    {
        $service = new GetAllAttendanceEligibleWorkersService();
        $this->workersOptionsItems = $service->executeForDropdown()->getResponse()['data'];
        return $this;
    }

    /**
     * Set options that will be used in the select element.
     * Gets all the work diaries for the given day.
     */
    private function setWorkDiariesOptionsItems()
    {
        $service = new GetAllWorkDiariesForDateService(new DateTime($this->date));
        $this->workDiaryOptionsItems = $service->executeForDropdown()->getResponse()['data'];
        return $this;
    }

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
     * Set options that will be used in the select element.
     * Gets all the attendance types.
     */
    protected function setTypeOptionsItems()
    {
        $this->workDayTypeItems = Types::bdeType();
    }

    protected function setFromParams(array $params)
    {
        foreach ($params as $key => $value) {
            match ($key) {
                'worker_id' => $this->checkIfWorkerIsSet($value),
                'date' => $this->checkIfDateIsSet($value),
            };
        }
        return $this;
    }

    /**
     * Check if there is a date set in the mount stage or over dispatch.
     * If yes add to the properties and disable the form(ignore if a att is set)
     */
    protected function checkIfDateIsSet(string|null $date)
    {
        if ($date !== null) {
            $this->attendance['date'] = $date;
            $this->date = $date;
            if (!$this->getAttValue('date-select.enable-changing')) $this->addDisableAttribute('dateInputAtt');
        }
        return $this;
    }

    /**
     * Check if there is a worker set in the mount stage or over dispatch.
     * If yes add to the properties and disable the form(ignore if a att is set)
     */
    protected function checkIfWorkerIsSet(int|null $worker)
    {
        if ($worker !== null) {
            $this->attendance['worker_id'] = $worker;
            $this->worker = $worker;
            if (!$this->getAttValue('worker-select.enable-changing')) $this->addDisableAttribute('workersSelectAtt');
        }
        return $this;
    }

    /**
     * Add a disabled true value do a attributes array
     */
    protected function addDisableAttribute(string $property)
    {
        if (property_exists(get_class($this), $property)) $this->$property['disabled'] = TRUE;
    }

    protected function setWorkerFromParams(int $id)
    {
        $this->attendance['worker_id'] = $id ?? null;
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
     * Update attendance date and refresh work diary options on date change.
     */
    public function updatedDate(string|null $value): void
    {
        $this->attendance['date'] = $value;

        if ($value === null || $value === '') {
            $this->workDiaryOptionsItems = [];
            return;
        }

        $this->setWorkDiariesOptionsItems();
    }


    /*
    |--------------------------------------------------------------------------
    | View & render
    |--------------------------------------------------------------------------
    */
    public function render()
    {
        return view('livewire.modules.working-hours.components.new-attendance-modal');
    }
}
