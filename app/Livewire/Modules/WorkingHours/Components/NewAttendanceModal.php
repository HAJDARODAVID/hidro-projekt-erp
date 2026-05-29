<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class NewAttendanceModal extends LivewireController
{
    public array $attendance = [
        'date' => '',
        'workDiary' => '',
        'hours' => '',
    ];

    public array $workDiaryOptions = [];

    public function mount()
    {
        $this->resetAttendance();
        $this->openModal();
    }

    #[On('open-new-attendance-modal')]
    public function initializeModal()
    {
        try {
            $this->resetAttendance();
            $this->openModal();
        } catch (\Throwable $th) {
            return $this->dispatch('show-exception-modal', $th->getMessage());
        }
    }

    protected function resetAttendance(): void
    {
        $this->attendance = [
            'date' => Carbon::today()->toDateString(),
            'workDiary' => '',
            'hours' => '',
        ];

        $this->workDiaryOptions = [];
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.new-attendance-modal');
    }
}
