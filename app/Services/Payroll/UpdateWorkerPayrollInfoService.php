<?php

namespace App\Services\Payroll;

use App\Models\PayrollBasicInfoModel;
use App\Services\BaseService;

/**
 * Class UpdateWorkerPayrollInfoService.
 * Saves the payroll settings of one worker (hour rate, fix rate, additional expenses, bonus
 * eligibility). Creates the worker's payroll_basic_info row if it doesn't exist yet.
 */
class UpdateWorkerPayrollInfoService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Save the payroll info for the worker.
     *
     * @param float $hourRate
     * @param float|null $fixRate
     * @param float $travelExpense
     * @param float $phoneExpense
     * @param bool $bonus
     * @return self
     */
    public function execute(float $hourRate, ?float $fixRate, float $travelExpense, float $phoneExpense, bool $bonus): self
    {
        try {
            $model = PayrollBasicInfoModel::updateOrCreate(
                ['worker_id' => $this->workerID],
                [
                    'h_rate'     => $hourRate,
                    'fix_rate'   => $fixRate,
                    'travel_exp' => $travelExpense,
                    'phone_exp'  => $phoneExpense,
                    'bonus'      => $bonus,
                ]
            );

            $this->setData(WorkerPayrollInfoDto::fromModel($model));
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }
}
