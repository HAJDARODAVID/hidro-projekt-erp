<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\On;
use App\Livewire\LivewireController;
use App\Models\Employees\SubcontractorWorker;
use App\Services\Subcontractors\UpdateSubcontractorWorkerService;

/**
 * Workers tab of the subcontractor module: all workers of the selected subcontractor company.
 */
class SubcontractorWorkers extends LivewireController
{
    /**Selected subcontractor company */
    public $subcontractorId = NULL;

    /**Active workers of the subcontractor, filtered by the search */
    public $workers = [];

    /**Filter the workers by name or ID */
    public $workerSearch = NULL;

    public function mount()
    {
        $this->getWorkers();
    }

    /**
     * Get the active workers of the subcontractor, filtered by the search.
     *
     * @return void
     */
    private function getWorkers(): void
    {
        $this->workers = SubcontractorWorker::ofSubcontractor((int) $this->subcontractorId)
            ->active()
            ->search($this->workerSearch)
            ->orderBy('firstName')
            ->orderBy('lastName')
            ->get(['id', 'firstName', 'lastName', 'status'])
            ->toArray();
    }

    /**
     * Runs after a new worker was created in the create modal. Reloads the list.
     *
     * @return void
     */
    #[On('subcontractor-worker-created')]
    public function onWorkerCreated(): void
    {
        $this->getWorkers();
    }

    /**
     * Runs after the search property has been updated.
     *
     * @return void
     */
    public function updatedWorkerSearch($value): void
    {
        if ($value == "") $this->workerSearch = NULL;
        $this->getWorkers();
    }

    /**
     * Reset the search input and reload the list.
     *
     * @return void
     */
    public function resetWorkerSearchInput(): void
    {
        $this->reset('workerSearch');
        $this->getWorkers();
    }

    /**
     * Switch the status of a worker (active <-> inactive) from the switch in the table.
     * Only active workers are listed, so a deactivated worker is removed from the table.
     *
     * @param int $id
     * @return void
     */
    public function toggleWorkerStatus($id): void
    {
        $service = UpdateSubcontractorWorkerService::byID((int) $id)->toggleStatus()->execute();
        if (!$service->getResponseStatus()) {
            $this->showException($service->getResponse()['message']);
            return;
        }

        $updated = $service->getResponse()['data'];
        if (!$updated->isActive()) {
            $this->workers = array_values(array_filter($this->workers, fn ($worker) => $worker['id'] != $id));
        }
        $this->notifyMe($updated->fullName . ': ' . translator($updated->statusLabel));
    }

    public function render()
    {
        return view('livewire.modules.employees.components.subcontractor-workers', [
            'statuses' => SubcontractorWorker::STATUSES,
        ]);
    }
}
