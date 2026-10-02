<?php

namespace App\Services\Subcontractors;

use App\Models\Employees\Subcontractor;
use App\Services\BaseService;

/**
 * Class GetAllSubcontractorsService.
 * Gets the subcontractor companies, ordered by name. The deleted ones are skipped by default.
 *
 * Usage:
 *  $service = GetAllSubcontractorsService::init()
 *      ->search('bau')          // optional, filter by name
 *      ->onlyActive()           // optional, skip the inactive ones
 *      ->withDeleted()          // optional, include the deleted ones
 *      ->withWorkersCount()     // optional, adds "workers_count"
 *      ->execute();
 *
 * The data is an array of the subcontractors (id, name, status [, workers_count]).
 */
class GetAllSubcontractorsService extends BaseService
{
    /**Filter by name (LIKE) */
    protected ?string $search = NULL;

    /**Only the active subcontractors */
    protected bool $onlyActive = FALSE;

    /**Include the deleted subcontractors */
    protected bool $withDeleted = FALSE;

    /**Add the number of workers of each subcontractor */
    protected bool $withWorkersCount = FALSE;

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
     * Filter the subcontractors by name. An empty value means no filter.
     *
     * @param string|null $search
     * @return self
     */
    public function search(?string $search): self
    {
        $search = trim((string) $search);
        $this->search = $search === '' ? NULL : $search;
        return $this;
    }

    /**
     * Get only the active subcontractors.
     *
     * @return self
     */
    public function onlyActive(): self
    {
        $this->onlyActive = TRUE;
        return $this;
    }

    /**
     * Include the deleted subcontractors as well (skipped by default).
     *
     * @return self
     */
    public function withDeleted(): self
    {
        $this->withDeleted = TRUE;
        return $this;
    }

    /**
     * Add the number of workers of each subcontractor (workers_count).
     *
     * @return self
     */
    public function withWorkersCount(): self
    {
        $this->withWorkersCount = TRUE;
        return $this;
    }

    public function execute(): self
    {
        try {
            $query = Subcontractor::query()
                ->select('id', 'name', 'status')
                ->search($this->search);

            if ($this->onlyActive) $query->active();
            if (!$this->onlyActive && !$this->withDeleted) $query->notDeleted();
            if ($this->withWorkersCount) $query->withCount('workers');

            $this->setData($query->orderBy('name', 'ASC')->get()->toArray());
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }
}
