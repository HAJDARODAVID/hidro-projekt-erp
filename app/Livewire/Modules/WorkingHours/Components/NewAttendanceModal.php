<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\AbsenceBtnObject;
use App\Services\Attendance\GetAllAttendanceEligibleWorkersService;
use App\Services\WorkdayDiary\Types;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class NewAttendanceModal extends LivewireController
{
    public array $attendance = [];

    public string|null $date = null;
    public array $dateInputAtt;

    public null|int|string $hourInput;
    public array $hourInputAtt;

    public array $workDiaryOptions = [];

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
                ->setFromParams($params);
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
        $this->setHoursInputAbsenceAtt($type);
    }

    /**
     * Removing selected absence type action.
     * This will remove and reset the $hourInput property.
     */
    public function resetHourInputAction()
    {
        $this->hourInput = null;
        $this->setHoursInputAtt();
    }

    /*
    |--------------------------------------------------------------------------
    | Setter and getters
    |--------------------------------------------------------------------------
    */

    /**
     * Set the default attendance array key's and values.
     */
    protected function resetAttendance()
    {
        $this->attendance = [
            'worker_id' => null,
            'working_day_record_id' => null,
            'type' => null,
            'work_hours' => null,
            'absence_reason' => null,
            'date' => Carbon::today()->toDateString(),
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

    public function render()
    {
        return view('livewire.modules.working-hours.components.new-attendance-modal');
    }
}
