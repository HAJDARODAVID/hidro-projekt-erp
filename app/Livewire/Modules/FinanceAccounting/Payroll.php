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
    protected $data = [];

    public function mount()
    {
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth = $this->selectedMonth == NULL ? date('n') : $this->selectedMonth;

        $this->years = Years::getYearsList();
        $this->selectedYear = $this->selectedYear == NULL ? date('Y') : $this->selectedYear;
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

    public function render()
    {
        $this->getPayrollData();
        return view('livewire.modules.finance-accounting.payroll', [
            'data' => $this->data,
        ]);
    }
}
