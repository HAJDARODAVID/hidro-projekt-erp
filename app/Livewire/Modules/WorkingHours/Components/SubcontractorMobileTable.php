<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Services\Attendance\SubcontractorHoursDataObject;

class SubcontractorMobileTable extends LivewireController
{
    public $tableData;

    public function render()
    {
        return view('livewire.modules.working-hours.components.subcontractor-mobile-table', [
            'data' => new SubcontractorHoursDataObject($this->tableData),
        ]);
    }
}
