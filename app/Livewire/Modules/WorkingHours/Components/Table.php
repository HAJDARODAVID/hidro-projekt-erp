<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Services\Attendance\WorkerHoursDataObject;
use App\Livewire\Modules\WorkingHours\Components\DayAttendanceForAllWorkersModal;

class Table extends LivewireController
{
    public $tableData;

    public function openDayAttendanceModal($date)
    {
        $this->dispatch('open-day-attendance-for-all-workers-modal', $date, (new WorkerHoursDataObject($this->tableData))->getWorkers())->to(DayAttendanceForAllWorkersModal::class);
    }

    public function openPerDayAndWorkerAttendanceModal($date)
    {
        $this->dispatch('open-per-day-and-worker-attendance-modal', $date, (new WorkerHoursDataObject($this->tableData))->getWorkers())->to(DayAttendanceForAllWorkersModal::class);
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.table', [
            'data' => new WorkerHoursDataObject($this->tableData),
        ]);
    }
}
