<?php

namespace App\Services\Subcontractors;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Subcontractor;
use App\Models\Employees\SubcontractorWorker;
use App\Services\BaseService;

/**
 * Class CreateSubcontractorWorkerService.
 * Creates a new worker of a subcontractor company.
 *
 * Usage:
 *  $service = CreateSubcontractorWorkerService::init($subcontractorID)
 *      ->setFirstName('Ivan')
 *      ->setLastName('Horvat')
 *      ->execute();
 *
 * The data is the created SubcontractorWorker.
 */
class CreateSubcontractorWorkerService extends BaseService
{
    protected int $subcontractorID;

    protected string $firstName = '';

    protected string $lastName = '';

    public function __construct(int $subcontractorID)
    {
        $this->subcontractorID = $subcontractorID;
    }

    /**
     * Create a new instance for the given subcontractor.
     *
     * @param int $subcontractorID
     * @return self
     */
    public static function init(int $subcontractorID): self
    {
        return new self($subcontractorID);
    }

    /**
     * Set the first name of the worker.
     *
     * @param string|null $firstName
     * @return self
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = trim((string) $firstName);
        return $this;
    }

    /**
     * Set the last name of the worker.
     *
     * @param string|null $lastName
     * @return self
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = trim((string) $lastName);
        return $this;
    }

    public function execute(): self
    {
        try {
            $this->validate();

            $worker = SubcontractorWorker::create([
                'cooperator_id' => $this->subcontractorID,
                'firstName'     => $this->firstName,
                'lastName'      => $this->lastName,
                'status'        => SubcontractorWorker::STATUS_ACTIVE,
            ]);
            $this->setSuccessMessage('Worker created!', $worker);
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
        if (!Subcontractor::find($this->subcontractorID)) throw new ErrorMessage(translator('The subcontractor does not exist.'));
        if ($this->firstName === '') throw new ErrorMessage(translator('The first name is required.'));
        if ($this->lastName === '') throw new ErrorMessage(translator('The last name is required.'));
    }
}
