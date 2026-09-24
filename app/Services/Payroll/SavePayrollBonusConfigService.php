<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\AppParametersModel;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Class SavePayrollBonusConfigService.
 * Creates/edits the company-wide payroll bonus amounts (monthly bonus, home day bonus, field day bonus).
 *
 * The amounts live in the legacy app_params table. The new value is set on the existing active row
 * (no history rows are kept). A parameter that does not exist yet is created.
 *
 * Usage:
 *  $service = (new SavePayrollBonusConfigService(
 *      (new PayrollBonusConfigDto())->setMonthlyBonus(100)->setHomeDayBonus(7)->setFieldDayBonus(23)
 *  ))->execute();
 *
 * The data is the saved PayrollBonusConfigDto (reloaded from the database).
 */
class SavePayrollBonusConfigService extends BaseService
{
    protected PayrollBonusConfigDto $config;

    public function __construct(PayrollBonusConfigDto $config)
    {
        $this->config = $config;
    }

    public function execute(): self
    {
        try {
            $this->validate();

            DB::transaction(function () {
                foreach (PayrollBonusConfigDto::PARAM_PROPERTIES as $paramKey => $property) {
                    $this->saveParam($paramKey, (float) $this->config->{'get' . ucfirst($property)}());
                }
            });

            $this->setSuccessMessage('Payroll bonus config saved!', PayrollBonusConfigDto::load());
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Every amount must be a positive number (0 allowed).
     *
     * @return void
     * @throws ErrorMessage
     */
    private function validate(): void
    {
        foreach (PayrollBonusConfigDto::PARAM_PROPERTIES as $property) {
            $value = $this->config->{'get' . ucfirst($property)}();
            if (!is_numeric($value) || (float) $value < 0) {
                throw new ErrorMessage(translator('Enter a valid, positive amount.'));
            }
        }
    }

    /**
     * Save one parameter: the value is set on the existing active row,
     * the row is only created when the parameter does not exist yet.
     *
     * @param string $paramKey
     * @param float $value
     * @return void
     */
    private function saveParam(string $paramKey, float $value): void
    {
        $value = number_format($value, 2, '.', '');

        $param = AppParametersModel::where('param_name_srt', $paramKey)
            ->where('active', TRUE)
            ->orderByDesc('id')
            ->first();

        if ($param === NULL) {
            AppParametersModel::create([
                'param_name'     => PayrollBonusConfigDto::PARAM_NAMES[$paramKey],
                'param_name_srt' => $paramKey,
                'value'          => $value,
                'active'         => TRUE,
            ]);
            return;
        }

        $param->update(['value' => $value]);
    }
}
