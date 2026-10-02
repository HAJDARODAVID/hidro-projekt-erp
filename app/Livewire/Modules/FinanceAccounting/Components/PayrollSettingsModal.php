<?php

namespace App\Livewire\Modules\FinanceAccounting\Components;

use App\Livewire\LivewireController;

class PayrollSettingsModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    public function mount()
    {
        $this->setTabs([
            'calculation' => translator('Calculation'),
            'bonus'       => translator('Bonus'),
        ]);
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.components.payroll-settings-modal');
    }
}
