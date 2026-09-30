<?php

namespace App\Services\Subcontractors;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Subcontractor;
use App\Services\BaseService;

/**
 * Class UpdateSubcontractorService.
 * Updates the info of one subcontractor company.
 * Only the values that were set are updated.
 *
 * Usage:
 *  $service = UpdateSubcontractorService::byID($id)
 *      ->setName('BAU DOM')                                       // optional
 *      ->setStatus(Subcontractor::STATUS_INACTIVE) // optional
 *      ->execute();
 *
 *  UpdateSubcontractorService::byID($id)->toggleStatus()->execute();
 *
 * The data is the updated Subcontractor.
 */
class UpdateSubcontractorService extends BaseService
{
    protected ?Subcontractor $subcontractor = NULL;

    /**[column => value] to update */
    protected array $values = [];

    /**Switch the status active <-> inactive on execute */
    protected bool $toggleStatus = FALSE;

    public function __construct(?Subcontractor $subcontractor)
    {
        $this->subcontractor = $subcontractor;
    }

    /**
     * Create the instance by the subcontractor ID.
     *
     * @param int $subcontractorID
     * @return self
     */
    public static function byID(int $subcontractorID): self
    {
        return new self(Subcontractor::find($subcontractorID));
    }

    /**
     * Set a new name.
     *
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->values['name'] = trim((string) $name);
        return $this;
    }

    /**
     * Set a new status (Subcontractor::STATUS_*).
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
     * Switch the status: active -> inactive, inactive -> active. Not allowed for a deleted subcontractor.
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
            if ($this->subcontractor === NULL) throw new ErrorMessage(translator('The subcontractor does not exist.'));

            if ($this->toggleStatus) {
                if ($this->subcontractor->isDeleted()) throw new ErrorMessage(translator('The subcontractor is deleted.'));
                $this->values['status'] = $this->subcontractor->isActive()
                    ? Subcontractor::STATUS_INACTIVE
                    : Subcontractor::STATUS_ACTIVE;
            }

            $this->validate();

            $this->subcontractor->update($this->values);
            $this->setSuccessMessage('Subcontractor updated!', $this->subcontractor);
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

        if (array_key_exists('name', $this->values) && $this->values['name'] === '') {
            throw new ErrorMessage(translator('The subcontractor name is required.'));
        }
        if (array_key_exists('status', $this->values) && !isset(Subcontractor::STATUSES[$this->values['status']])) {
            throw new ErrorMessage(translator('The subcontractor status is not valid.'));
        }
    }
}
