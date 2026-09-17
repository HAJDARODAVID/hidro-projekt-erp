<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Services\Attendance\MonthlyHoursOverviewReportService;
use App\Services\BaseService;

/**
 * Class GetAllPayrollDataService.
 * Gathers the payroll data for all workers for a given month/year.
 */
class GetAllPayrollDataService extends BaseService
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
     * Generate the payroll data for the selected period.
     * Each row is expected to hold: worker, hours, rate, gross, deductions, net.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $output = [];

            $monthlyHoursOverviewReportService = (new MonthlyHoursOverviewReportService($this->month, $this->year))->execute();
            if ($monthlyHoursOverviewReportService->getResponseStatus()) {
                $output = $monthlyHoursOverviewReportService->getData();
            } else {
                throw new ErrorMessage($monthlyHoursOverviewReportService->getResponse()['message']);
            }
            dd($output);

            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }
}
