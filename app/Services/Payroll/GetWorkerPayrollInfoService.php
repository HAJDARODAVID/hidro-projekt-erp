<?php

namespace App\Services\Payroll;

use App\Services\BaseService;
use App\Models\PayrollBasicInfoModel;

/**
 * Class GetWorkerPayrollInfoService.
 * Gets all the information of a worker that defines the payroll (hour rate, fix rate, additional expenses, bonus).
 * The data is returned as a WorkerPayrollInfoDto. If the worker has no payroll info a DTO with defaults is returned.
 */
class GetWorkerPayrollInfoService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Get the payroll info for the worker.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $model = PayrollBasicInfoModel::where('worker_id', $this->workerID)->first();

            $dto = $model
                ? WorkerPayrollInfoDto::fromModel($model)
                : (new WorkerPayrollInfoDto())->setWorkerID($this->workerID);

            $this->setData($dto);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Shortcut: get the DTO directly, or NULL on failure.
     *
     * @param int $workerID
     * @return WorkerPayrollInfoDto|null
     */
    public static function byWorker(int $workerID): ?WorkerPayrollInfoDto
    {
        $service = (new self($workerID))->execute();
        return $service->getResponseStatus() ? $service->getResponse()['data'] : NULL;
    }
}
