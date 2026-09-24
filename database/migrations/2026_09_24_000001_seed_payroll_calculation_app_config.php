<?php

use App\Models\Application\AppConfig;
use App\Services\Payroll\PayrollCalculationConfigDto;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = json_encode(PayrollCalculationConfigDto::DEFAULTS);

        AppConfig::firstOrCreate(['key' => 'payroll_calculation'], [
            'value'         => $defaults,
            'default_value' => $defaults,
            'label'         => 'Payroll – Calculation',
            'description'   => 'User defined rules for the payroll base and net calculation.',
            'data_type'     => 'json',
            'is_public'     => true,
            'is_locked'     => false,
        ]);
    }

    public function down(): void
    {
        AppConfig::where('key', 'payroll_calculation')->delete();
    }
};
