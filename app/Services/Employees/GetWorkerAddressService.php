<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Models\Employees\WorkerAddress;

/**
 * Class GetWorkerAddressService.
 * Gets the address of a worker (street, town, zip, county).
 * The data is returned as a WorkerAddressDto. If the worker has no address a DTO with empty values is returned.
 *
 * Usage:
 *  $service = (new GetWorkerAddressService($workerID))->execute();
 *  $dto = GetWorkerAddressService::byWorker($workerID); // or NULL
 */
class GetWorkerAddressService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Get the address of the worker.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $model = WorkerAddress::ofWorker($this->workerID)->first();

            $dto = $model
                ? WorkerAddressDto::fromModel($model)
                : (new WorkerAddressDto())->setWorkerID($this->workerID);

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
     * @return WorkerAddressDto|null
     */
    public static function byWorker(int $workerID): ?WorkerAddressDto
    {
        $service = (new self($workerID))->execute();
        return $service->getResponseStatus() ? $service->getResponse()['data'] : NULL;
    }
}
