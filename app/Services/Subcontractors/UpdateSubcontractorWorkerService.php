<?php

namespace App\Services\Subcontractors;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\SubcontractorWorker;
use App\Services\BaseService;

/**
 * Class UpdateSubcontractorWorkerService.
 * Updates the info of one subcontractor worker.
 * Only the values that were set are updated.
 *
 * Usage:
 *  $service = UpdateSubcontractorWorkerService::byID($id)
 *      ->setStatus(SubcontractorWorker::STATUS_INACTIVE) // optional
 *      ->execute();
 *
 *  UpdateSubcontractorWorkerService::byID($id)->toggleStatus()->execute();
 *
 * The data is the updated SubcontractorWorker.
 */
class UpdateSubcontractorWorkerService extends BaseService
{
    protected ?SubcontractorWorker $worker = NULL;

    /**[column => value] to update */
    protected array $values = [];

    /**Switch the status active <-> inactive on execute */
    protected bool $toggleStatus = FALSE;

    public function __construct(?SubcontractorWorker $worker)
    {
        $this->worker = $worker;
    }

    /**
     * Create the instance by the worker ID.
     *
     * @param int $workerID
     * @return self
     */
    public static function byID(int $workerID): self
    {
        return new self(SubcontractorWorker::find($workerID));
    }

    /**
     * Set a new status (SubcontractorWorker::STATUS_*).
     *
     * @param int $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->values['status'] = $status;
        return $this;
    }

    /**
     * Switch the status: active -> inactive, anything else -> active.
     *
     * @return self
     */
    public function toggleStatus(): self
    {
        $this->toggleStatus = TRUE;
        return $this;
    }

    public function execute(): self
    {
        try {
            if ($this->worker === NULL) throw new ErrorMessage(translator('The worker does not exist.'));

            if ($this->toggleStatus) {
                $this->values['status'] = $this->worker->isActive()
                    ? SubcontractorWorker::STATUS_INACTIVE
                    : SubcontractorWorker::STATUS_ACTIVE;
            }

            $this->validate();

            $this->worker->update($this->values);
            $this->setSuccessMessage('Worker updated!', $this->worker);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Check the values before they are saved.
     *
     * @return void
     * @throws ErrorMessage
     */
    private function validate(): void
    {
        if (empty($this->values)) throw new ErrorMessage(translator('Nothing to update.'));

        if (array_key_exists('status', $this->values) && !isset(SubcontractorWorker::STATUSES[$this->values['status']])) {
            throw new ErrorMessage(translator('The worker status is not valid.'));
        }
    }
}
