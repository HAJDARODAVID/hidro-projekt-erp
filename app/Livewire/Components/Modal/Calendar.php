<?php

namespace App\Livewire\Components\Modal;

use App\Livewire\LivewireController;
use App\Services\Application\CalendarService;
use DateTime;

class Calendar extends LivewireController
{
    public ?int $month = NULL;
    public ?int $year = NULL;

    public array $calendarDates = [];

    public ?\DateTime $selectedDate = null;

    public function mount()
    {
        $this->selectedDate = new DateTime('2026-04-27');
        $this->getDates();
        $this->openModal();
    }

    private function getDates()
    {
        if ($this->month == null || $this->year == null) {
            $now = now();
            if ($this->month == null) $this->month = $now->format('n');
            if ($this->year == null) $this->year = $now->format('Y');
        }
        $service = new CalendarService($this->month, $this->year);
        $service = $service->getResponse();
        if ($service['success']) {
            $this->calendarDates = $service['data'];
        } else {
            $this->showException($service['message']);
        }
        return $this;
    }

    public function selectDate()
    {
        dd("selectDate");
    }

    public function render()
    {
        return view('livewire.components.modal.calendar');
    }
}
