<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Exceptions\ErrorMessage;

/**
 * Class GetWorkerFullInfoService.
 * Gets all the info of a worker by running GetWorkerInfoService, GetWorkerAddressService and GetWorkerContactService.
 * The data is returned as a WorkerFullInfoDto. Fails when one of the services fails (e.g. the worker does not exist).
 *
 * Usage:
 *  $service = (new GetWorkerFullInfoService($workerID))->execute();
 *  $dto = GetWorkerFullInfoService::byWorker($workerID); // or NULL
 *  $dto->getInfo()->getFullName(); $dto->getAddress()->getTown(); $dto->getContact()->getEmail();
 */
class GetWorkerFullInfoService extends BaseService
{
    /** @var int */
    protected $workerID;

    public function __construct(int $workerID)
    {
        $this->workerID = $workerID;
    }

    /**
     * Get the info, address and contact info of the worker.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $this->setData((new WorkerFullInfoDto())
                ->setInfo($this->run(new GetWorkerInfoService($this->workerID)))
                ->setAddress($this->run(new GetWorkerAddressService($this->workerID)))
                ->setContact($this->run(new GetWorkerContactService($this->workerID))));
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Shortcut: get the DTO directly, or NULL on failure.
     *
     * @param int $workerID
     * @return WorkerFullInfoDto|null
     */
    public static function byWorker(int $workerID): ?WorkerFullInfoDto
    {
        $service = (new self($workerID))->execute();
        return $service->getResponseStatus() ? $service->getResponse()['data'] : NULL;
    }

    /**
     * Execute one of the worker services and return its data, its error message is passed on.
     *
     * @param BaseService $service
     * @return mixed
     * @throws ErrorMessage
     */
    private function run(BaseService $service): mixed
    {
        $service->execute();
        $response = $service->getResponse();
        if (!$service->getResponseStatus()) throw new ErrorMessage($response['message']);

        return $response['data'];
    }
}
