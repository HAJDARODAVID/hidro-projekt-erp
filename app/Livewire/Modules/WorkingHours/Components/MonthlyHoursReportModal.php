<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Exceptions\ArraySearchTraitException;
use App\Exports\Attendance\MonthlyHoursReportExport;
use App\Services\Attendance\MonthlyHoursReportExportDto;
use App\Services\Attendance\MonthlyHoursOverviewReportService;
use App\Services\Months;
use App\Services\Years;

class MonthlyHoursReportModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    /**Options for the month/year selects */
    public $months = [];
    public $years = [];

    /**Date[month, year] for the report*/
    public $selectedMonth = NULL, $selectedYear = NULL;

    /**This will be used when you search for a specific worker */
    public $workerSearch;

    /**All the report data */
    public $data;

    public function boot()
    {
        $this->setSearchProperty('workerSearch')
            ->setArraySearchProperty('data')
            ->setArraySearchKey('name');
    }

    /**
     * The global modal mounts a fresh instance of this component every time
     * it's opened, so this is where the report data is loaded for the
     * month/year that were passed in as params.
     */
    public function mount()
    {
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth = $this->params['month'] ?? date('n');

        $this->years = Years::getYearsList();
        $this->selectedYear = $this->params['year'] ?? date('Y');

        $this->loadReportData();
    }

    /**
     * Fetch the report data for the current month/year.
     */
    private function loadReportData()
    {
        $service = NULL;
        try {
            $service = (new MonthlyHoursOverviewReportService($this->selectedMonth, $this->selectedYear))->execute();
        } catch (\Throwable $th) {
            $this->showException($th->getMessage());
            return;
        }

        $service = $service->getResponse();
        if ($service['success']) {
            $this->data = $service['data'];
            $this->enableArraySearch();
        } else {
            $this->showException($service['message']);
        }
    }

    public function exportMonthlyHoursAction()
    {
        return (new MonthlyHoursReportExport(new MonthlyHoursReportExportDto($this->data, ['month' => $this->selectedMonth, 'year' => $this->selectedYear])));
    }

    /**
     * Run when the month is changed and reload the report data
     *
     * @return void
     */
    public function updatedSelectedMonth(): void
    {
        $this->loadReportData();
    }

    /**
     * Run when the year is changed and reload the report data
     *
     * @return void
     */
    public function updatedSelectedYear(): void
    {
        $this->loadReportData();
    }

    public function updatedWorkerSearch($value)
    {
        if ($value == "") $this->workerSearch = NULL;
        $this->searchArray();
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.monthly-hours-report-modal');
    }
}
