<?php

namespace App\Services\Payroll;

use App\Services\BaseDTO;
use App\Models\AppParametersModel;

/**
 * Class PayrollBonusConfigDto.
 * Holds the company-wide payroll bonus amounts used in the calculation.
 * Load it once and pass it to every CalculateWorkerPayrollService to avoid repeated lookups.
 */
class PayrollBonusConfigDto extends BaseDTO
{
    /**App parameter keys (legacy app_params table) */
    const PARAM_MONTHLY_BONUS   = 'adm-wb';
    const PARAM_HOME_DAY_BONUS  = 'adm-fwb-home';
    const PARAM_FIELD_DAY_BONUS = 'adm-fwb-field';

    /**App parameter key => DTO property */
    const PARAM_PROPERTIES = [
        self::PARAM_MONTHLY_BONUS   => 'monthlyBonus',
        self::PARAM_HOME_DAY_BONUS  => 'homeDayBonus',
        self::PARAM_FIELD_DAY_BONUS => 'fieldDayBonus',
    ];

    /**Display names (app_params.param_name) used when a parameter does not exist yet */
    const PARAM_NAMES = [
        self::PARAM_MONTHLY_BONUS   => 'Bonus na plaću',
        self::PARAM_HOME_DAY_BONUS  => 'Bonus za teren',
        self::PARAM_FIELD_DAY_BONUS => 'Bonus za more',
    ];

    /**Monthly bonus [€] for eligible workers without sick leave */
    protected $monthlyBonus = 0.0;

    /**Bonus [€] per day worked at home */
    protected $homeDayBonus = 0.0;

    /**Bonus [€] per day worked in the field */
    protected $fieldDayBonus = 0.0;

    /**
     * Load the values from the active app parameters (app_params keeps the old values as inactive rows).
     * Use SavePayrollBonusConfigService to change them.
     *
     * @return self
     */
    public static function load(): self
    {
        $params = AppParametersModel::whereIn('param_name_srt', array_keys(self::PARAM_PROPERTIES))
            ->where('active', TRUE)
            ->orderBy('id')
            ->pluck('value', 'param_name_srt');

        return (new self())
            ->setMonthlyBonus((float) ($params[self::PARAM_MONTHLY_BONUS] ?? 0))
            ->setHomeDayBonus((float) ($params[self::PARAM_HOME_DAY_BONUS] ?? 0))
            ->setFieldDayBonus((float) ($params[self::PARAM_FIELD_DAY_BONUS] ?? 0));
    }

    /**
     * Get the value of monthlyBonus
     */
    public function getMonthlyBonus()
    {
        return $this->monthlyBonus;
    }

    /**
     * Set the value of monthlyBonus
     *
     * @return  self
     */
    public function setMonthlyBonus($monthlyBonus)
    {
        $this->monthlyBonus = $monthlyBonus;

        return $this;
    }

    /**
     * Get the value of homeDayBonus
     */
    public function getHomeDayBonus()
    {
        return $this->homeDayBonus;
    }

    /**
     * Set the value of homeDayBonus
     *
     * @return  self
     */
    public function setHomeDayBonus($homeDayBonus)
    {
        $this->homeDayBonus = $homeDayBonus;

        return $this;
    }

    /**
     * Get the value of fieldDayBonus
     */
    public function getFieldDayBonus()
    {
        return $this->fieldDayBonus;
    }

    /**
     * Set the value of fieldDayBonus
     *
     * @return  self
     */
    public function setFieldDayBonus($fieldDayBonus)
    {
        $this->fieldDayBonus = $fieldDayBonus;

        return $this;
    }
}
