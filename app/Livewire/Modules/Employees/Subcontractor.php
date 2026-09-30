<?php

namespace App\Livewire\Modules\Employees;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Models\Employees\Subcontractor as SubcontractorModel;
use App\Services\Subcontractors\GetAllSubcontractorsService;
use App\Services\Subcontractors\UpdateSubcontractorService;

class Subcontractor extends LivewireController
{
    #[Url('search')]
    public $subcontractorSearch = NULL;

    /**Selected subcontractor company */
    #[Url('subcontractor')]
    public $selectedSubcontractor = NULL;

    /**Subcontractor companies shown in the list */
    public $subcontractors = [];

    public function mount()
    {
        $this->setTabs([
            'workers' => 'Workers',
        ]);
        $this->getSubcontractors();
    }

    /**
     * Get all the subcontractor companies (active and inactive), filtered by the search.
     *
     * @return void
     */
    private function getSubcontractors(): void
    {
        $service = GetAllSubcontractorsService::init()
            ->search($this->subcontractorSearch)
            ->withWorkersCount()
            ->execute();

        if (!$service->getResponseStatus()) {
            $this->subcontractors = [];
            $this->showException($service->getResponse()['message']);
            return;
        }
        $this->subcontractors = $service->getResponse()['data'];
    }

    /**
     * Switch the status of a subcontractor (active <-> inactive) from the switch in the list.
     *
     * @param int $id
     * @return void
     */
    public function toggleSubcontractorStatus($id): void
    {
        $service = UpdateSubcontractorService::byID((int) $id)->toggleStatus()->execute();
        if (!$service->getResponseStatus()) {
            $this->showException($service->getResponse()['message']);
        } else {
            $updated = $service->getResponse()['data'];
            $this->notifyMe($updated->name . ': ' . translator($updated->statusLabel));
        }
        $this->getSubcontractors();
    }

    /**
     * Runs after a new subcontractor was created in the create modal.
     * Clears the search so the new one is visible in the list, and selects it.
     *
     * @param int $id
     * @return void
     */
    #[On('subcontractor-created')]
    public function onSubcontractorCreated($id): void
    {
        $this->reset('subcontractorSearch');
        $this->selectedSubcontractor = (int) $id;
        $this->getSubcontractors();
    }

    /**
     * Runs after the search property has been updated.
     *
     * @return void
     */
    public function updatedSubcontractorSearch($value): void
    {
        if ($value == "") $this->subcontractorSearch = NULL;
        $this->getSubcontractors();
    }

    /**
     * Reset the search input and reload the list.
     *
     * @return void
     */
    public function resetSubcontractorSearchInput(): void
    {
        $this->reset('subcontractorSearch');
        $this->getSubcontractors();
    }

    /**
     * Select a subcontractor company and show its info. Clicking the selected one again deselects it.
     *
     * @return void
     */
    public function selectSubcontractor($id): void
    {
        $this->selectedSubcontractor = $this->selectedSubcontractor == $id ? NULL : (int) $id;
    }

    public function render()
    {
        return view('livewire.modules.employees.subcontractor', [
            'subcontractor' => $this->selectedSubcontractor ? SubcontractorModel::find($this->selectedSubcontractor) : NULL,
        ]);
    }
}
