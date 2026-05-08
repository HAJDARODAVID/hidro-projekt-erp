<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;

class NewAttendanceModal extends LivewireController
{
    public function render()
    {
        return view('livewire.modules.working-hours.components.new-attendance-modal');
    }
}
