<?php

namespace App\Livewire\Modules\FinanceAccounting\Components;

use App\Livewire\LivewireController;
use App\Livewire\Modules\FinanceAccounting\Payroll;
use App\Services\Config\PayrollCalculationConfigService;
use App\Services\Payroll\PayrollCalculationConfigDto;

/**
 * Calculation tab of the payroll settings modal.
 * The user defines how the base (hours * rate / fixed rate) and the net (component operations)
 * are calculated. The rules are global and apply to every unlocked payroll.
 */
class PayrollCalculationSettings extends LivewireController
{
    /**Hour sources counted in the base hours */
    public array $baseHours = [];

    /**Hours counted per leave/holiday/sick day */
    public $hoursPerDay = NULL;

    /**Whether the worker's fixed rate replaces hours * hourRate */
    public bool $useFixRate = TRUE;

    /**[component => operation] of the net calculation */
    public array $netComponents = [];

    public function mount()
    {
        $this->fillFromConfig(PayrollCalculationConfigDto::load());
    }

    /**
     * Validate and save the rules, then refresh the payroll table behind the modal.
     *
     * @return void
     */
    public function saveBtn()
    {
        try {
            $config = (new PayrollCalculationConfigDto())
                ->setBaseHours($this->baseHours)
                ->setHoursPerDay((float) $this->hoursPerDay)
                ->setUseFixRate($this->useFixRate);
            foreach ($this->netComponents as $component => $operation) {
                $config->setNetComponent($component, $operation);
            }

            (new PayrollCalculationConfigService())->save($config);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('Payroll calculation saved'));
        $this->dispatch('refresh-payroll-data')->to(Payroll::class);
    }

    /**
     * Put the form back to the original calculation (not saved until the save button is clicked).
     *
     * @return void
     */
    public function resetToDefaultBtn()
    {
        $this->fillFromConfig(PayrollCalculationConfigDto::fromArray(PayrollCalculationConfigDto::DEFAULTS));
    }

    /**
     * Fill the form properties from the config.
     *
     * @param PayrollCalculationConfigDto $config
     * @return void
     */
    private function fillFromConfig(PayrollCalculationConfigDto $config): void
    {
        $this->baseHours     = $config->getBaseHours();
        $this->hoursPerDay   = $config->getHoursPerDay();
        $this->useFixRate    = $config->getUseFixRate();
        $this->netComponents = $config->getNetComponents();
    }

    public function render()
    {
        return view('livewire.modules.finance-accounting.components.payroll-calculation-settings', [
            'hourSources' => PayrollCalculationConfigDto::HOUR_SOURCES,
            'components'  => PayrollCalculationConfigDto::COMPONENTS,
            'operations'  => PayrollCalculationConfigDto::OPERATIONS,
        ]);
    }
}
