<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Models\Employees\WorkerContact;

/**
 * Class GetWorkerContactService.
 * Gets the contact info of a worker (mobile phone, email).
 * The data is returned as a WorkerContactDto. If the worker has no contact info a DTO with empty values is returned.
 *
 * Usage:
 *  $service = (new GetWorkerContactService($workerID))->execute();
 *  $dto = GetWorkerContactService::byWorker($workerID); // or NULL
 */
class GetWorkerContactService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Get the contact info of the worker.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $model = WorkerContact::ofWorker($this->workerID)->first();

            $dto = $model
                ? WorkerContactDto::fromModel($model)
                : (new WorkerContactDto())->setWorkerID($this->workerID);

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
     * @return WorkerContactDto|null
     */
    public static function byWorker(int $workerID): ?WorkerContactDto
    {
        $service = (new self($workerID))->execute();
        return $service->getResponseStatus() ? $service->getResponse()['data'] : NULL;
    }
}
