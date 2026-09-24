<?php

namespace App\Services\Payroll;

use App\Services\BaseDTO;
use App\Exceptions\ErrorMessage;
use App\Services\Config\PayrollCalculationConfigService;

/**
 * Class PayrollCalculationConfigDto.
 * The user defined rules of the payroll calculation (global, not bound to a payroll).
 * Stored as JSON in the app_configs table (key: payroll_calculation).
 *
 *  - base  = fixRate when "useFixRate" is on and the worker has one,
 *            otherwise baseHours * hourRate, where baseHours is the sum of the selected hour sources
 *  - gross = sum of the net components set to "add"
 *  - net   = gross - sum of the net components set to "subtract"
 *
 * The defaults reproduce the original (hardcoded) calculation.
 * Load it once and pass it to every CalculateWorkerPayrollService to avoid repeated lookups.
 */
class PayrollCalculationConfigDto extends BaseDTO
{
    /**Hour sources that can be counted in the base hours */
    const HOURS_WORK        = 'work-hours';
    const HOURS_PAID_LEAVE  = 'paid-leave';
    const HOURS_HOLIDAY     = 'holiday';
    const HOURS_SICK_LEAVE  = 'sick-leave';

    const HOUR_SOURCES = [
        self::HOURS_WORK       => 'Work hours',
        self::HOURS_PAID_LEAVE => 'Paid leave (PL)',
        self::HOURS_HOLIDAY    => 'Holiday (HD)',
        self::HOURS_SICK_LEAVE => 'Sick leave (SL)',
    ];

    /**Components of the net calculation */
    const COMPONENT_BASE           = 'base';
    const COMPONENT_HOME_BONUS     = 'home-bonus';
    const COMPONENT_FIELD_BONUS    = 'field-bonus';
    const COMPONENT_BONUS          = 'bonus';
    const COMPONENT_TRAVEL_EXPENSE = 'travel-expense';
    const COMPONENT_PHONE_EXPENSE  = 'phone-expense';
    const COMPONENT_DEDUCTIONS     = 'deductions';

    const COMPONENTS = [
        self::COMPONENT_BASE           => 'Base',
        self::COMPONENT_HOME_BONUS     => 'Home days bonus',
        self::COMPONENT_FIELD_BONUS    => 'Field days bonus',
        self::COMPONENT_BONUS          => 'Monthly bonus',
        self::COMPONENT_TRAVEL_EXPENSE => 'Travel expense',
        self::COMPONENT_PHONE_EXPENSE  => 'Phone expense',
        self::COMPONENT_DEDUCTIONS     => 'Deductions',
    ];

    /**What a component does in the net calculation */
    const OPERATION_ADD      = 'add';
    const OPERATION_SUBTRACT = 'subtract';
    const OPERATION_IGNORE   = 'ignore';

    const OPERATIONS = [
        self::OPERATION_ADD      => 'Add',
        self::OPERATION_SUBTRACT => 'Subtract',
        self::OPERATION_IGNORE   => 'Ignore',
    ];

    /**The original calculation, used when nothing is saved yet */
    const DEFAULTS = [
        'base' => [
            'hours'             => [self::HOURS_WORK, self::HOURS_PAID_LEAVE, self::HOURS_HOLIDAY],
            'hours-per-day'     => 8,
            'use-fix-rate'      => TRUE,
        ],
        'net' => [
            self::COMPONENT_BASE           => self::OPERATION_ADD,
            self::COMPONENT_HOME_BONUS     => self::OPERATION_ADD,
            self::COMPONENT_FIELD_BONUS    => self::OPERATION_ADD,
            self::COMPONENT_BONUS          => self::OPERATION_ADD,
            self::COMPONENT_TRAVEL_EXPENSE => self::OPERATION_ADD,
            self::COMPONENT_PHONE_EXPENSE  => self::OPERATION_ADD,
            self::COMPONENT_DEDUCTIONS     => self::OPERATION_SUBTRACT,
        ],
    ];

    /**Hour sources summed into the base hours */
    protected array $baseHours = self::DEFAULTS['base']['hours'];

    /**Hours counted per leave/holiday day */
    protected float $hoursPerDay = self::DEFAULTS['base']['hours-per-day'];

    /**Whether the worker's fixed rate replaces hours * hourRate */
    protected bool $useFixRate = self::DEFAULTS['base']['use-fix-rate'];

    /**[component => operation] */
    protected array $netComponents = self::DEFAULTS['net'];

    /**
     * Load the saved config, falling back to the defaults for anything missing.
     *
     * @return self
     */
    public static function load(): self
    {
        $value = (new PayrollCalculationConfigService())->getValue();
        return self::fromArray(is_array($value) ? $value : []);
    }

    /**
     * Build the DTO from the stored array. Unknown values are dropped,
     * missing ones keep their defaults.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        $base = $data['base'] ?? [];
        if (isset($base['hours']) && is_array($base['hours'])) {
            $dto->setBaseHours($base['hours']);
        }
        if (isset($base['hours-per-day']) && is_numeric($base['hours-per-day'])) {
            $dto->setHoursPerDay((float) $base['hours-per-day']);
        }
        if (array_key_exists('use-fix-rate', $base)) {
            $dto->setUseFixRate(filter_var($base['use-fix-rate'], FILTER_VALIDATE_BOOLEAN));
        }

        foreach (($data['net'] ?? []) as $component => $operation) {
            if (isset(self::COMPONENTS[$component]) && isset(self::OPERATIONS[$operation])) {
                $dto->netComponents[$component] = $operation;
            }
        }

        return $dto;
    }

    /**
     * The array stored in the app config.
     *
     * @return array
     */
    public function toConfigArray(): array
    {
        return [
            'base' => [
                'hours'         => $this->baseHours,
                'hours-per-day' => $this->hoursPerDay,
                'use-fix-rate'  => $this->useFixRate,
            ],
            'net' => $this->netComponents,
        ];
    }

    /**
     * Check the config makes sense before it is saved.
     *
     * @return self
     * @throws ErrorMessage
     */
    public function validate(): self
    {
        if (empty($this->baseHours) && !$this->useFixRate) {
            throw new ErrorMessage(translator('Select at least one hour source or enable the fixed rate.'));
        }
        if ($this->hoursPerDay <= 0 || $this->hoursPerDay > 24) {
            throw new ErrorMessage(translator('Hours per day must be between 0 and 24.'));
        }
        if (!in_array(self::OPERATION_ADD, $this->netComponents, TRUE)) {
            throw new ErrorMessage(translator('At least one component must be added to the net.'));
        }
        return $this;
    }

    /**
     * Sum the selected hour sources of the monthly hours report.
     * Leave/holiday/sick days are converted to hours with hoursPerDay.
     *
     * @param float $workHours Logged work hours
     * @param int $paidLeaveDays
     * @param int $holidayDays
     * @param int $sickLeaveDays
     * @return float
     */
    public function calculateBaseHours(float $workHours, int $paidLeaveDays, int $holidayDays, int $sickLeaveDays): float
    {
        $sources = [
            self::HOURS_WORK       => $workHours,
            self::HOURS_PAID_LEAVE => $paidLeaveDays * $this->hoursPerDay,
            self::HOURS_HOLIDAY    => $holidayDays * $this->hoursPerDay,
            self::HOURS_SICK_LEAVE => $sickLeaveDays * $this->hoursPerDay,
        ];

        $hours = 0.0;
        foreach ($this->baseHours as $source) {
            $hours += $sources[$source] ?? 0;
        }
        return $hours;
    }

    /**
     * Gross and net from the component amounts, following the configured operations.
     *
     * @param array $amounts [component => amount], amounts are positive (deductions too)
     * @return array ['gross' => float, 'net' => float]
     */
    public function calculateGrossAndNet(array $amounts): array
    {
        $gross = 0.0;
        $subtract = 0.0;
        foreach ($this->netComponents as $component => $operation) {
            $amount = (float) ($amounts[$component] ?? 0);
            if ($operation === self::OPERATION_ADD) $gross += $amount;
            elseif ($operation === self::OPERATION_SUBTRACT) $subtract += $amount;
        }
        return ['gross' => $gross, 'net' => $gross - $subtract];
    }

    /**
     * Get the value of baseHours
     */
    public function getBaseHours(): array
    {
        return $this->baseHours;
    }

    /**
     * Set the value of baseHours, unknown sources are dropped
     *
     * @return  self
     */
    public function setBaseHours(array $baseHours): self
    {
        $this->baseHours = array_values(array_intersect(array_keys(self::HOUR_SOURCES), $baseHours));

        return $this;
    }

    /**
     * Get the value of hoursPerDay
     */
    public function getHoursPerDay(): float
    {
        return $this->hoursPerDay;
    }

    /**
     * Set the value of hoursPerDay
     *
     * @return  self
     */
    public function setHoursPerDay(float $hoursPerDay): self
    {
        $this->hoursPerDay = $hoursPerDay;

        return $this;
    }

    /**
     * Get the value of useFixRate
     */
    public function getUseFixRate(): bool
    {
        return $this->useFixRate;
    }

    /**
     * Set the value of useFixRate
     *
     * @return  self
     */
    public function setUseFixRate(bool $useFixRate): self
    {
        $this->useFixRate = $useFixRate;

        return $this;
    }

    /**
     * Get the value of netComponents
     */
    public function getNetComponents(): array
    {
        return $this->netComponents;
    }

    /**
     * Set the operation of one net component
     *
     * @return  self
     */
    public function setNetComponent(string $component, string $operation): self
    {
        if (isset(self::COMPONENTS[$component]) && isset(self::OPERATIONS[$operation])) {
            $this->netComponents[$component] = $operation;
        }

        return $this;
    }
}
