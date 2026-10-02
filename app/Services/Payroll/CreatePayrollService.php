<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Payroll\Payroll;
use App\Services\BaseService;

/**
 * Class CreatePayrollService.
 * Creates a new payroll for a month/year.
 */
class CreatePayrollService extends BaseService
{
    /** @var int */
    protected $month;

    /** @var int */
    protected $year;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Create the payroll. The data is the created Payroll model.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $this->validatePeriod();

            if (Payroll::forPeriod($this->month, $this->year)->exists()) {
                throw new ErrorMessage('A payroll for ' . $this->month . '/' . $this->year . ' already exists.');
            }

            $payroll = Payroll::create([
                'month'  => $this->month,
                'year'   => $this->year,
                'locked' => FALSE,
            ]);

            $this->setSuccessMessage('Payroll created!', $payroll);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Check that the month and year make sense.
     *
     * @throws ErrorMessage
     */
    private function validatePeriod(): void
    {
        if ($this->month < 1 || $this->month > 12) throw new ErrorMessage('The month must be between 1 and 12.');
        if ($this->year < 2000) throw new ErrorMessage('The year is not valid.');
    }
}
