<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\Subcontractor as SubcontractorModel;
use App\Livewire\Modules\Employees\Subcontractor;
use App\Services\Subcontractors\CreateSubcontractorService;

class CreateSubcontractorModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    public $name = NULL;

    public $status = SubcontractorModel::STATUS_ACTIVE;

    /**
     * Create the subcontractor, then close the modal and refresh the list behind it.
     *
     * @return void
     */
    public function confirmBtn()
    {
        $service = CreateSubcontractorService::init()
            ->setName($this->name)
            ->setStatus((int) $this->status)
            ->execute();

        $response = $service->getResponse();
        if (!$service->getResponseStatus()) return $this->showException($response['message']);

        $this->closeGlobalModal();
        $this->dispatch('subcontractor-created', $response['data']->id)->to(Subcontractor::class);
        $this->notifyMe(translator('Subcontractor created!'));
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
        return view('livewire.modules.employees.components.create-subcontractor-modal', [
            'statuses' => array_map(fn ($label) => translator($label), SubcontractorModel::SELECTABLE_STATUSES),
        ]);
    }
}
