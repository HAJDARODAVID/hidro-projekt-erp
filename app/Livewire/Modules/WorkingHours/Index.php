<?php

namespace App\Livewire\Modules\WorkingHours;

use App\Services\Years;
use App\Services\Months;
use Livewire\Attributes\Url;
use App\Livewire\ExceptionModal;
use App\Livewire\LivewireController;
use App\Services\Employees\GetWorkersMonthlyHoursReportService;
use Livewire\Attributes\On;

class Index extends LivewireController
{
    public $months = [];
    public $years = [];

    #[Url('month')]
    public $selectedMonth = NULL;

    #[Url('year')]
    public $selectedYear = NULL;

    protected $data = [];

    public function mount()
    {
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth =  $this->selectedMonth == NULL ? date('n') :  $this->selectedMonth;

        $this->years = Years::getYearsList();
        $this->selectedYear =  $this->selectedYear == NULL ? date('Y') :  $this->selectedYear;
    }

    private function getWorkerHoursReportData()
    {
        $service = new GetWorkersMonthlyHoursReportService($this->selectedMonth, $this->selectedYear);
        $service = $service->execute();
        if ($service['success']) {
            $this->data = $service['data'];
        } else {
            $this->data = [];
            $this->dispatch('show-exception-modal', $service['message'])->to(ExceptionModal::class);
        }
        $this->data['info']['date'] = ['month' => $this->selectedMonth, 'year' => $this->selectedYear];
    }

    #[On('refresh-attendance-report')]
    public function refreshMe() {}

    public function render()
    {
        $this->getWorkerHoursReportData();
        return view('livewire.modules.working-hours.index', [
            'data' =>  $this->data
        ]);
    }
}
