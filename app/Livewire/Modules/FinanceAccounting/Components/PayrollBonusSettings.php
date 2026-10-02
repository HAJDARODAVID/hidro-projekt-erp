<?php

namespace App\Livewire\Modules\FinanceAccounting\Components;

use App\Livewire\LivewireController;
use App\Livewire\Modules\FinanceAccounting\Payroll;
use App\Services\Payroll\PayrollBonusConfigDto;
use App\Services\Payroll\SavePayrollBonusConfigService;

/**
 * Bonus tab of the payroll settings modal.
 * Edits the company-wide bonus amounts used in the payroll calculation (global, apply to every unlocked payroll).
 */
class PayrollBonusSettings extends LivewireController
{
    /**Monthly bonus [€] for eligible workers without sick leave */
    public $monthlyBonus = NULL;

    /**Bonus [€] per day worked at home */
    public $homeDayBonus = NULL;

    /**Bonus [€] per day worked in the field */
    public $fieldDayBonus = NULL;

    public function mount()
    {
        $this->fillFromConfig(PayrollBonusConfigDto::load());
    }

    /**
     * Save the amounts, then refresh the payroll table behind the modal.
     *
     * @return void
     */
    public function saveBtn()
    {
        $this->saved = [];

        $service = (new SavePayrollBonusConfigService(
            (new PayrollBonusConfigDto())
                ->setMonthlyBonus($this->monthlyBonus)
                ->setHomeDayBonus($this->homeDayBonus)
                ->setFieldDayBonus($this->fieldDayBonus)
        ))->execute();

        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        $this->fillFromConfig($service->getResponse()['data']);
        $this->savedSuccess('monthlyBonus', 'homeDayBonus', 'fieldDayBonus');
        $this->notifyMe(translator('Payroll bonus saved'));
        $this->dispatch('refresh-payroll-data')->to(Payroll::class);
    }

    /**
     * Fill the form properties from the config.
     *
     * @param PayrollBonusConfigDto $config
     * @return void
     */
    private function fillFromConfig(PayrollBonusConfigDto $config): void
    {
        $this->monthlyBonus  = $config->getMonthlyBonus();
        $this->homeDayBonus  = $config->getHomeDayBonus();
        $this->fieldDayBonus = $config->getFieldDayBonus();
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.components.payroll-bonus-settings');
    }
}
