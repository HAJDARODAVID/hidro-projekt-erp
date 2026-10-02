<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\SubcontractorWorker;
use App\Services\Subcontractors\CreateSubcontractorWorkerService;
use App\Services\Subcontractors\UpdateSubcontractorWorkerService;

class CreateSubcontractorWorkerModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    public $subcontractorId = NULL;

    public $firstName = NULL;

    public $lastName = NULL;

    /**Workers of the subcontractor with the same first and last name, shown in the warning */
    public $existingWorkers = [];

    public function mount()
    {
        $this->subcontractorId = isset($this->params['subcontractorId']) ? (int) $this->params['subcontractorId'] : NULL;
    }

    /**
     * Hide the warning when the name changes, it was for the old name.
     *
     * @return void
     */
    public function updatedFirstName(): void
    {
        $this->existingWorkers = [];
    }

    /**
     * Hide the warning when the name changes, it was for the old name.
     *
     * @return void
     */
    public function updatedLastName(): void
    {
        $this->existingWorkers = [];
    }

    /**
     * Create the worker. If a worker (active or inactive) with the same name already exists,
     * show a warning instead, so the user can create a new one anyway or activate the inactive one.
     *
     * @return void
     */
    public function confirmBtn()
    {
        // An empty name can't match anyone, let the service show the validation message
        if (trim((string) $this->firstName) === '' || trim((string) $this->lastName) === '') return $this->createWorker();

        $this->existingWorkers = SubcontractorWorker::ofSubcontractor((int) $this->subcontractorId)
            ->withName($this->firstName, $this->lastName)
            ->orderByDesc('status')
            ->orderBy('id')
            ->get(['id', 'firstName', 'lastName', 'status'])
            ->toArray();

        if (!empty($this->existingWorkers)) return;

        $this->createWorker();
    }

    /**
     * Create the worker even though a worker with the same name already exists.
     *
     * @return void
     */
    public function createAnyway()
    {
        $this->createWorker();
    }

    /**
     * Activate an existing inactive worker (from the warning) instead of creating a new one.
     *
     * @param int $id
     * @return void
     */
    public function activateWorker($id)
    {
        $isListed = collect($this->existingWorkers)->contains(
            fn ($worker) => $worker['id'] == $id && $worker['status'] != SubcontractorWorker::STATUS_ACTIVE
        );
        if (!$isListed) return $this->showException(translator('The worker does not exist.'));

        $service = UpdateSubcontractorWorkerService::byID((int) $id)
            ->setStatus(SubcontractorWorker::STATUS_ACTIVE)
            ->execute();

        $response = $service->getResponse();
        if (!$service->getResponseStatus()) return $this->showException($response['message']);

        $this->finish(translator('Worker activated!'));
    }

    /**
     * Create the worker with the entered name.
     *
     * @return void
     */
    private function createWorker()
    {
        $service = CreateSubcontractorWorkerService::init((int) $this->subcontractorId)
            ->setFirstName($this->firstName)
            ->setLastName($this->lastName)
            ->execute();

        $response = $service->getResponse();
        if (!$service->getResponseStatus()) return $this->showException($response['message']);

        $this->finish(translator('Worker created!'));
    }

    /**
     * Close the modal, refresh the workers list behind it and notify the user.
     *
     * @param string $message
     * @return void
     */
    private function finish(string $message): void
    {
        $this->closeGlobalModal();
        $this->dispatch('subcontractor-worker-created')->to(SubcontractorWorkers::class);
        $this->notifyMe($message);
    }

    /**
     * Close the global modal without modifying its shared component.
     * The global modal already closes on Escape (see global-modal.blade.php),
     * so a synthetic Escape keypress reuses that existing listener.
     *
     * @return void
     */
    private function closeGlobalModal(): void
    {
        $this->js("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))");
    }

    public function render()
    {
        return view('livewire.modules.employees.components.create-subcontractor-worker-modal', [
            'statuses' => SubcontractorWorker::STATUSES,
        ]);
    }
}
