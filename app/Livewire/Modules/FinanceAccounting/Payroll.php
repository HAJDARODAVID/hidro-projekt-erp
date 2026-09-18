<?php

namespace App\Livewire\Modules\FinanceAccounting;

use App\Services\Years;
use App\Services\Months;
use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Services\Payroll\GetAllPayrollDataService;

class Payroll extends LivewireController
{
    public $months = [];
    public $years = [];

    #[Url('month')]
    public $selectedMonth = NULL;

    #[Url('year')]
    public $selectedYear = NULL;

    /**Payroll rows for the selected period */
    public $data = [];

    /**Search value for filtering the rows by worker name or ID */
    public $search = '';

    public function mount()
    {
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth = $this->selectedMonth == NULL ? date('n') : $this->selectedMonth;

        $this->years = Years::getYearsList();
        $this->selectedYear = $this->selectedYear == NULL ? date('Y') : $this->selectedYear;

        $this->getPayrollData();
    }

    public function updatedSelectedMonth()
    {
        $this->getPayrollData();
    }

    public function updatedSelectedYear()
    {
        $this->getPayrollData();
    }

    /**
     * Populate the payroll data for the selected month/year using the service.
     *
     * @return self
     */
    private function getPayrollData()
    {
        try {
            $service = (new GetAllPayrollDataService((int) $this->selectedMonth, (int) $this->selectedYear))->execute();
            if ($service->getResponse()['success']) {
                $this->data = $service->getResponse()['data'];
            } else {
                $this->data = [];
                $this->showException($service->getResponse()['message']);
            }
        } catch (\Throwable $th) {
            $this->data = [];
            $this->showException($th->getMessage());
        }
        return $this;
    }

    /**
     * Filter the payroll rows by the search value (worker name or ID).
     * The original array keys are kept so the inputs stay bound to the right row.
     *
     * @return array
     */
    private function getFilteredRows(): array
    {
        $search = trim((string) $this->search);
        if ($search === '') return $this->data;

        /**IDs are shown zero padded, so compare against the search without the leading zeros */
        $idSearch = ltrim($search, '0');

        return array_filter($this->data, function ($row) use ($search, $idSearch) {
            if (mb_stripos((string) $row['name'], $search) !== FALSE) return TRUE;
            return $idSearch !== '' && str_contains((string) $row['workerID'], $idSearch);
        });
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.payroll', [
            'rows' => $this->getFilteredRows(),
        ]);
    }
}
