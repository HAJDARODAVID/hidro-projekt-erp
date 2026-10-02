<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Payroll\Payroll;
use App\Services\BaseService;

/**
 * Class GetPayrollService.
 * Gets one payroll either by its ID or by month/year.
 * The payroll items and deductions are loaded with it.
 */
class GetPayrollService extends BaseService
{
    /** @var int|null */
    protected $payrollID = NULL;

    /** @var int|null */
    protected $month = NULL;

    /** @var int|null */
    protected $year = NULL;

    /**Relations loaded with the payroll */
    protected array $with = ['getPayrollItems', 'getDeductions'];

    /**
     * Get the payroll by its ID.
     *
     * @param int $payrollID
     * @return self
     */
    public static function byID(int $payrollID): self
    {
        $service = new self();
        $service->payrollID = $payrollID;
        return $service;
    }

    /**
     * Get the payroll by month/year.
     *
     * @param int $month
     * @param int $year
     * @return self
     */
    public static function byPeriod(int $month, int $year): self
    {
        $service = new self();
        $service->month = $month;
        $service->year = $year;
        return $service;
    }

    /**
     * Override the relations loaded with the payroll.
     *
     * @param string ...$relations
     * @return self
     */
    public function with(string ...$relations): self
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Get the payroll. The data is the Payroll model.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $payroll = $this->buildQuery()->with($this->with)->first();
            if ($payroll === NULL) throw new ErrorMessage('Payroll not found (' . $this->describeLookup() . ').');

            $this->setData($payroll);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Shortcut: get the Payroll model directly, or NULL when it does not exist.
     *
     * @return Payroll|null
     */
    public function get(): ?Payroll
    {
        $this->execute();
        return $this->getResponseStatus() ? $this->getResponse()['data'] : NULL;
    }

    /**
     * Build the query for the requested lookup.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws ErrorMessage
     */
    private function buildQuery()
    {
        if ($this->payrollID !== NULL) return Payroll::where('id', $this->payrollID);

        if ($this->month !== NULL && $this->year !== NULL) {
            if ($this->month < 1 || $this->month > 12) throw new ErrorMessage('The month must be between 1 and 12.');
            return Payroll::forPeriod($this->month, $this->year);
        }

        throw new ErrorMessage('Set the payroll ID or the month/year to get a payroll.');
    }

    /**
     * Describe the lookup for the error message.
     *
     * @return string
     */
    private function describeLookup(): string
    {
        return $this->payrollID !== NULL ? 'ID ' . $this->payrollID : $this->month . '/' . $this->year;
    }
}
