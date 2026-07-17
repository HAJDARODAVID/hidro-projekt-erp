<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\AbsenceBtnObject;

class WorkerAttendancePerDay extends LivewireController
{
    public array $params = [];

    public array $constructionSites = [];
    public array $groupLeaders = [];
    public array $workersOptionsItems = [];

    public null|int|string $hourInput;
    public array $hourInputAtt;

    public function mount()
    {
        $this->setHoursInputAtt();
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

    public function render()
    {
        return view('livewire.modules.working-hours.components.worker-attendance-per-day');
    }
}
