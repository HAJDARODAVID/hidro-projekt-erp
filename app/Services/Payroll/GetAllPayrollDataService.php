<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Worker;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;
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

    /**Whether the payroll of the period is locked, set once execute() has run */
    protected bool $locked = FALSE;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Is the payroll of the period locked. Only meaningful after execute() has run.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Generate the payroll data for the selected period.
     * Each row is expected to hold: worker, hours, rate, gross, deductions, net.
     * A locked payroll is frozen, so its saved items are returned as is; an unlocked one is
     * recalculated against the live attendance, keeping its saved items in sync with it.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            /**Payroll items of the period: saved items hand over the editable values, missing ones get created */
            $itemsSync = (new SyncPayrollItemsService($this->month, $this->year))->execute();
            if (!$itemsSync->getResponseStatus()) throw new ErrorMessage($itemsSync->getResponse()['message']);

            $this->locked = (bool) $itemsSync->getPayroll()->locked;

            $output = $this->locked
                ? $this->buildLockedOutput($itemsSync)
                : $this->buildCalculatedOutput($itemsSync);

            /**Sort the rows by worker ID */
            ksort($output, SORT_NUMERIC);
            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * A locked payroll is frozen: hand back the saved payroll data of its items as is,
     * no attendance lookup or recalculation needed.
     *
     * @param SyncPayrollItemsService $itemsSync
     * @return array Keyed by worker ID
     */
    private function buildLockedOutput(SyncPayrollItemsService $itemsSync): array
    {
        $output = [];
        foreach ($itemsSync->getItems() as $workerID => $item) {
            $output[$workerID] = $item->payroll_data;
        }
        return $output;
    }

    /**
     * An unlocked payroll is recalculated against the live attendance for every worker, so
     * changes made after the items were saved (e.g. attendance edits) are picked up.
     *
     * @param SyncPayrollItemsService $itemsSync
     * @return array Keyed by worker ID
     * @throws ErrorMessage
     */
    private function buildCalculatedOutput(SyncPayrollItemsService $itemsSync): array
    {
        $monthlyHoursOverviewReportService = (new MonthlyHoursOverviewReportService($this->month, $this->year))->execute();
        if (!$monthlyHoursOverviewReportService->getResponseStatus()) throw new ErrorMessage($monthlyHoursOverviewReportService->getResponse()['message']);

        /**Load the bonus amounts once for all workers */
        $bonusConfig = PayrollBonusConfigDto::load();

        $output = [];
        foreach ($this->buildHoursDtos($monthlyHoursOverviewReportService->getData()) as $workerID => $monthlyHoursDto) {
            $calculation = (new CalculateWorkerPayrollService($monthlyHoursDto))
                ->setBonusConfig($bonusConfig)
                ->setEditableValues($itemsSync->getEditableValues($workerID))
                ->execute();
            if (!$calculation->getResponseStatus()) throw new ErrorMessage($calculation->getResponse()['message']);

            /** @var WorkerPayrollCalculationDto $calculationDto */
            $calculationDto = $calculation->getResponse()['data'];
            $itemsSync->createItemIfMissing($workerID, $calculationDto);
            $itemsSync->updateItemIfExists($workerID, $calculationDto);

            $output[$workerID] = $calculationDto->toArray();
        }
        return $output;
    }

    /**
     * Build one hours DTO per worker for the payroll.
     * Starts with the workers found in the attendance report, then appends every
     * active worker (payroll type) who has no attendance rows in the period,
     * so they still show up in the payroll with zero hours.
     *
     * @param array $reportData Output of the MonthlyHoursOverviewReportService keyed by worker ID
     * @return MonthlyHoursOverviewReportDto[] Keyed by worker ID
     */
    private function buildHoursDtos(array $reportData): array
    {
        $dtos = [];
        foreach ($reportData as $workerID => $data) {
            $dtos[$workerID] = MonthlyHoursOverviewReportDto::fromArray($data)->setWorkerID($workerID);
        }

        $activeWorkers = Worker::where('status', WorkerStatus::WORKER_STATUS_ACTIVE)
            ->whereIn('type', WorkerType::init()->getTypesForPayroll())
            ->whereNotIn('id', array_keys($dtos))
            ->orderBy('firstName')
            ->orderBy('lastName')
            ->get();

        foreach ($activeWorkers as $worker) {
            $dtos[$worker->id] = (new MonthlyHoursOverviewReportDto())
                ->setWorkerID($worker->id)
                ->setName($worker->fullName)
                ->setStatus($worker->status);
        }

        return $dtos;
    }
}
