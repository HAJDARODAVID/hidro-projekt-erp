<?php

namespace App\Livewire\Modules\WorkingHours;

use App\Services\Years;
use App\Services\Months;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Livewire\ExceptionModal;
use App\Livewire\LivewireController;
use App\Services\Subcontractors\GetSubcontractorsMonthlyHoursReportService;

class Subcontractor extends LivewireController
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

    /**
     * Call the service and store the report data.
     * Throw the exception modal if the service fails.
     */
    private function getSubcontractorHoursReportData()
    {
        $service = new GetSubcontractorsMonthlyHoursReportService($this->selectedMonth, $this->selectedYear);
        $service = $service->execute();
        if ($service['success']) {
            $this->data = $service['data'];
        } else {
            $this->data = [];
            $this->dispatch('show-exception-modal', $service['message'])->to(ExceptionModal::class);
        }
        $this->data['info']['date'] = ['month' => $this->selectedMonth, 'year' => $this->selectedYear];
    }

    #[On('refresh-subcontractor-hours-report')]
    public function refreshMe() {}

    public function render()
    {
        $this->getSubcontractorHoursReportData();
        return view('livewire.modules.working-hours.subcontractor', [
            'data' =>  $this->data
        ]);
    }
}
