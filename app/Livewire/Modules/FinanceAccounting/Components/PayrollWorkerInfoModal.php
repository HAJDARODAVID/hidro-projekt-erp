<?php

namespace App\Livewire\Modules\FinanceAccounting\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\Worker;

class PayrollWorkerInfoModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    /**Worker the modal was opened for */
    public $workerID = NULL;

    /**Zero-padded worker ID + full name, shown next to the tabs */
    public $workerLabel = NULL;

    public $selectedMonth = NULL;
    public $selectedYear = NULL;

    public function mount()
    {
        $this->workerID = isset($this->params['workerID']) ? (int) $this->params['workerID'] : NULL;
        $this->selectedMonth = $this->params['month'] ?? date('n');
        $this->selectedYear = $this->params['year'] ?? date('Y');

        $worker = $this->workerID ? Worker::find($this->workerID) : NULL;
        $this->workerLabel = $worker
            ? str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) . ' - ' . $worker->fullName
            : NULL;

        $this->setTabs([
            'payroll-info' => translator('Payroll info'),
            'deductions'   => translator('Deductions'),
            'attendance'   => translator('Attendance'),
        ]);
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.components.payroll-worker-info-modal');
    }
}
