<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Services\Attendance\MonthlyHoursOverviewReportDto;
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
            $monthlyHoursOverviewReportData = [];

            $monthlyHoursOverviewReportService = (new MonthlyHoursOverviewReportService($this->month, $this->year))->execute();
            if ($monthlyHoursOverviewReportService->getResponseStatus()) {
                $monthlyHoursOverviewReportData = $monthlyHoursOverviewReportService->getData();
            } else {
                throw new ErrorMessage($monthlyHoursOverviewReportService->getResponse()['message']);
            }
            /**Load the bonus amounts once for all workers */
            $bonusConfig = PayrollBonusConfigDto::load();

            $output = [];
            foreach ($monthlyHoursOverviewReportData as $workerID => $data) {
                $monthlyHoursDto = MonthlyHoursOverviewReportDto::fromArray($data)->setWorkerID($workerID);

                $calculation = (new CalculateWorkerPayrollService($monthlyHoursDto))
                    ->setBonusConfig($bonusConfig)
                    ->execute();
                if (!$calculation->getResponseStatus()) throw new ErrorMessage($calculation->getResponse()['message']);

                $output[$workerID] = $calculation->getResponse()['data']->toArray();
            }
            //dd($output);
            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }
}
