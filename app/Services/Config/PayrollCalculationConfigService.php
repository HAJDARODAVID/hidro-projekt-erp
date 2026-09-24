<?php

namespace App\Services\Config;

use App\Services\Payroll\PayrollCalculationConfigDto;

/**
 * Reads/writes the user defined payroll calculation rules (app_configs, key: payroll_calculation).
 * Use PayrollCalculationConfigDto::load() to get the rules in the calculation.
 */
class PayrollCalculationConfigService extends BaseConfigService
{
    const CONFIG_KEY = 'payroll_calculation';

    protected string|array $configKeys = self::CONFIG_KEY;

    /**
     * Validate and save the rules. The config record is created on the first save.
     *
     * @param PayrollCalculationConfigDto $config
     * @return bool
     */
    public function save(PayrollCalculationConfigDto $config): bool
    {
        $value = $config->validate()->toConfigArray();

        if ($this->getConfig() === NULL) {
            $this->createConfig(
                (new AppConfigDto())
                    ->setKey(self::CONFIG_KEY)
                    ->setValue($value)
                    ->setDefaultValue(PayrollCalculationConfigDto::DEFAULTS)
                    ->setLabel('Payroll – Calculation')
                    ->setDescription('User defined rules for the payroll base and net calculation.')
                    ->setDataType('json')
                    ->setIsPublic(TRUE)
            );
            $this->invalidateCache(self::CONFIG_KEY);
            return TRUE;
        }

        return $this->setValue((new AppConfigDto())->setKey(self::CONFIG_KEY)->setValue($value));
    }
}
