<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Services\Years;
use App\Services\Months;
use App\Models\CooperatorsModel;
use App\Livewire\LivewireController;
use App\Exports\Subcontractors\SubcontractorHoursExport;
use App\Services\Attendance\SubcontractorHoursDataObject;
use App\Services\Subcontractors\SubcontractorHoursListExportDto;
use App\Services\Subcontractors\SubcontractorHoursSummaryExportDto;
use App\Services\Subcontractors\GetSubcontractorHoursExportDataService;
use App\Services\Subcontractors\GetSubcontractorsMonthlyHoursReportService;

class SubcontractorHoursExportModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    /**Options for the month/year selects */
    public $months = [];
    public $years = [];

    /**Date[month, year] for the export*/
    public $selectedMonth = NULL, $selectedYear = NULL;

    /**
     * Subcontractors shown in the modal for the selected month/year.
     * id => ['name' => string, 'workers' => int, 'hours' => float, 'cost' => string]
     */
    public $subcontractors = [];

    /**Sum of all the subcontractors ['hours' => float, 'cost' => string] */
    public $total = ['hours' => 0, 'cost' => ''];

    /**
     * The global modal mounts a fresh instance of this component every time
     * it's opened, so this is where the list is loaded for the
     * month/year that were passed in as params.
     */
    public function mount()
    {
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth = $this->params['month'] ?? date('n');

        $this->years = Years::getYearsList();
        $this->selectedYear = $this->params['year'] ?? date('Y');

        $this->loadSubcontractors();
    }

    /**
     * Build the subcontractors list for the selected month/year.
     * Subcontractors with hours come from the monthly report, the active ones without hours are added with zero,
     * so the user can see that there is nothing to export for them.
     */
    private function loadSubcontractors()
    {
        $this->subcontractors = [];
        $this->total = ['hours' => 0, 'cost' => ''];

        $service = (new GetSubcontractorsMonthlyHoursReportService($this->selectedMonth, $this->selectedYear))->execute();
        if (!$service['success']) {
            $this->showException($service['message']);
            return;
        }
        $data = $service['data'];
        $data['info']['date'] = ['month' => $this->selectedMonth, 'year' => $this->selectedYear];
        $data = new SubcontractorHoursDataObject($data);

        foreach ($data->getSubcontractors() as $id => $info) {
            $this->subcontractors[$id] = [
                'name'    => $info['name'],
                'workers' => count($data->getWorkers($id)),
                'hours'   => $data->subcontractorTotal($id),
                'cost'    => $data->formatCost($data->subcontractorCost($id)),
            ];
        }

        /**Active subcontractors without hours */
        $active = CooperatorsModel::where('status', CooperatorsModel::COOPERATORS_STATUS_ACTIVE)->orderBy('name', 'ASC')->get();
        foreach ($active as $subcontractor) {
            if (isset($this->subcontractors[$subcontractor->id])) continue;
            $this->subcontractors[$subcontractor->id] = [
                'name'    => $subcontractor->name,
                'workers' => 0,
                'hours'   => 0,
                'cost'    => $data->formatCost(0),
            ];
        }
        uasort($this->subcontractors, fn($a, $b) => strcmp($a['name'], $b['name']));

        $this->total = [
            'hours' => $data->total(),
            'cost'  => $data->formatCost($data->totalCost()),
        ];
    }

    /**
     * Run when the month is changed and reload the list
     *
     * @return void
     */
    public function updatedSelectedMonth(): void
    {
        $this->loadSubcontractors();
    }

    /**
     * Run when the year is changed and reload the list
     *
     * @return void
     */
    public function updatedSelectedYear(): void
    {
        $this->loadSubcontractors();
    }

    /**
     * Export the hours of a single subcontractor.
     *
     * @param int $subcontractorID
     */
    public function exportSubcontractorAction($subcontractorID)
    {
        return $this->export((int) $subcontractorID);
    }

    /**
     * Export the hours of all the subcontractors in one file.
     */
    public function exportAllAction()
    {
        return $this->export(NULL);
    }

    /**
     * Build the excel export.
     * Show a notification if there is nothing to export.
     *
     * @param int|NULL $subcontractorID NULL exports all the subcontractors
     * @return SubcontractorHoursExport|void
     */
    private function export(?int $subcontractorID)
    {
        try {
            $service = (new GetSubcontractorHoursExportDataService($this->selectedMonth, $this->selectedYear, $subcontractorID))->execute()->getResponse();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        if (!$service['success']) return $this->notifyMe($service['message'], 'warning');

        $data = $service['data'];
        $name = $data['subcontractor']['name'] ?? translator('All subcontractors');
        $addInfo = [
            'subcontractor' => $name,
            'month'         => $this->selectedMonth,
            'year'          => $this->selectedYear,
        ];

        return new SubcontractorHoursExport(
            new SubcontractorHoursSummaryExportDto($data['summary'], $addInfo),
            new SubcontractorHoursListExportDto($data['list'], $addInfo),
            $name,
            (int) $this->selectedMonth,
            (int) $this->selectedYear,
        );
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.subcontractor-hours-export-modal');
    }
}
