<?php

namespace App\Livewire\Modules\FinanceAccounting;

use App\Services\Years;
use App\Services\Months;
use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Services\Payroll\GetAllPayrollDataService;
use App\Services\Payroll\UpdatePayrollItemService;

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

    /**Whether the payroll of the selected period is locked */
    public $locked = FALSE;

    /**Search value for filtering the rows by worker name or ID */
    #[Url('search')]
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
     * Run when a value is changed in the payroll table (data.{workerID}.{field}).
     * Updates the payroll item, refreshes the row, and marks the input as saved (is-valid).
     *
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updatedData($value, $key)
    {
        $this->saved = [];
        [$workerID, $field] = explode('.', $key);
        $workerID = (int) $workerID;

        try {
            $service = UpdatePayrollItemService::forWorker((int) $this->selectedMonth, (int) $this->selectedYear, $workerID);
            switch ($field) {
                case 'hourRate':
                    $service->updateHourRate($value);
                    break;
                case 'travelExpense':
                    $service->updateTravelExpense($value);
                    break;
                case 'phoneExpense':
                    $service->updatePhoneExpense($value);
                    break;
                case 'bonus':
                    $service->updateBonus($value);
                    break;
                default:
                    return;
            }
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        if ($service->getResponseStatus()) {
            $this->data[$workerID] = $service->getResponse()['data']->toArray();
            $this->saved['data.' . $workerID . '.' . $field] = TRUE;
        } else {
            $this->showException($service->getResponse()['message']);
        }
    }

    /**
     * Populate the payroll data for the selected month/year using the service.
     *
     * @return self
     */
    private function getPayrollData()
    {
        try {
            $this->saved = [];
            $service = (new GetAllPayrollDataService((int) $this->selectedMonth, (int) $this->selectedYear))->execute();
            if ($service->getResponse()['success']) {
                $this->data = $service->getResponse()['data'];
                $this->locked = $service->isLocked();
            } else {
                $this->data = [];
                $this->locked = FALSE;
                $this->showException($service->getResponse()['message']);
            }
        } catch (\Throwable $th) {
            $this->data = [];
            $this->locked = FALSE;
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
