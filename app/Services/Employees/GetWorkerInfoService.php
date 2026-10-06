<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Exceptions\ErrorMessage;
use App\Models\Employees\Worker;

/**
 * Class GetWorkerInfoService.
 * Gets the basic info of a worker (name, OIB, employment dates, workplace, status, type).
 * The data is returned as a WorkerInfoDto. Fails when the worker does not exist.
 *
 * Usage:
 *  $service = (new GetWorkerInfoService($workerID))->execute();
 *  $dto = GetWorkerInfoService::byWorker($workerID); // or NULL
 */
class GetWorkerInfoService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Get the info of the worker.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $model = Worker::find($this->workerID);
            if ($model === NULL) throw new ErrorMessage(translator('The worker does not exist.'));

            $this->setData(WorkerInfoDto::fromModel($model));
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Shortcut: get the DTO directly, or NULL on failure.
     *
     * @param int $workerID
     * @return WorkerInfoDto|null
     */
    public static function byWorker(int $workerID): ?WorkerInfoDto
    {
        $service = (new self($workerID))->execute();
        return $service->getResponseStatus() ? $service->getResponse()['data'] : NULL;
    }
}
