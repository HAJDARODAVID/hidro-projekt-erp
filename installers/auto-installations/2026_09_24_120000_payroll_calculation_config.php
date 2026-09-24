<?php

use App\Models\Application\AppConfig;
use App\Services\Config\PayrollCalculationConfigService;
use App\Services\Payroll\PayrollCalculationConfigDto;

return new class {

    /**
     * Installation type identifier
     */
    public function getType(): string
    {
        return 'app_config';
    }

    /**
     * Data to store for audit trail
     */
    public function getData(): array
    {
        return [
            'key'   => PayrollCalculationConfigService::CONFIG_KEY,
            'value' => PayrollCalculationConfigDto::DEFAULTS,
        ];
    }

    /**
     * Seed the payroll calculation rules (app_configs) with the defaults.
     * An already existing config is left untouched, so saved rules are never overwritten.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('⚙️ Installing the payroll calculation config...');

        try {
            $defaults = json_encode(PayrollCalculationConfigDto::DEFAULTS);

            AppConfig::firstOrCreate(['key' => PayrollCalculationConfigService::CONFIG_KEY], [
                'value'         => $defaults,
                'default_value' => $defaults,
                'label'         => 'Payroll – Calculation',
                'description'   => 'User defined rules for the payroll base and net calculation.',
                'data_type'     => 'json',
                'is_public'     => TRUE,
                'is_locked'     => FALSE,
            ]);

            \Illuminate\Support\Facades\Log::info('✓ Payroll calculation config installed successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('✗ Failed to install the payroll calculation config: ' . $e->getMessage());
            throw $e;
        }
    }
};
