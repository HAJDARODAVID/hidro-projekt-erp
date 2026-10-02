<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Services\Attendance\WorkerHoursDataObject;

class MobileTable extends LivewireController
{
    public $tableData;

    public function openDayAttendanceModal($date)
    {
        $this->dispatch('open-global-modal', component: 'day-attendance-for-all-workers', params: [
            'date' => $date,
            'workers' => (new WorkerHoursDataObject($this->tableData))->getWorkers(),
        ]);
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.mobile-table', [
            'data' => new WorkerHoursDataObject($this->tableData),
        ]);
    }
}
