<?php

namespace App\Services\Subcontractors;

use App\Exceptions\ErrorMessage;
use App\Models\Employees\Subcontractor;
use App\Services\BaseService;

/**
 * Class CreateSubcontractorService.
 * Creates a new subcontractor company.
 *
 * Usage:
 *  $service = CreateSubcontractorService::init()
 *      ->setName('BAU DOM')
 *      ->setStatus(Subcontractor::STATUS_INACTIVE) // optional, active by default
 *      ->execute();
 *
 * The data is the created Subcontractor.
 */
class CreateSubcontractorService extends BaseService
{
    protected string $name = '';

    protected int $status = Subcontractor::STATUS_ACTIVE;

    /**
     * Create a new instance.
     *
     * @return self
     */
    public static function init(): self
    {
        return new self();
    }

    /**
     * Set the name of the subcontractor.
     *
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = trim((string) $name);
        return $this;
    }

    /**
     * Set the status (Subcontractor::STATUS_*).
     *
     * @param int $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function execute(): self
    {
        try {
            $this->validate();

            $subcontractor = Subcontractor::create([
                'name'   => $this->name,
                'status' => $this->status,
            ]);
            $this->setSuccessMessage('Subcontractor created!', $subcontractor);
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
        if ($this->name === '') throw new ErrorMessage(translator('The subcontractor name is required.'));

        if (!isset(Subcontractor::SELECTABLE_STATUSES[$this->status])) {
            throw new ErrorMessage(translator('The subcontractor status is not valid.'));
        }
        if (Subcontractor::notDeleted()->where('name', $this->name)->exists()) {
            throw new ErrorMessage(translator('A subcontractor with this name already exists.'));
        }
    }
}
