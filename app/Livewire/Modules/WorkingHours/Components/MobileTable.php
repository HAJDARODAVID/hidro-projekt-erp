<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Services\Attendance\WorkerHoursDataObject;
use App\Livewire\Modules\WorkingHours\Components\DayAttendanceForAllWorkersModal;

class MobileTable extends LivewireController
{
    public $tableData;

    public function openDayAttendanceModal($date)
    {
        $this->dispatch('open-day-attendance-for-all-workers-modal', $date, (new WorkerHoursDataObject($this->tableData))->getWorkers())->to(DayAttendanceForAllWorkersModal::class);
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.mobile-table', [
            'data' => new WorkerHoursDataObject($this->tableData),
        ]);
    }
}
