<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\Workplace;
use App\Models\Employees\WorkerStatus;
use App\Services\Application\Acl\AclService;
use App\Services\Employees\CreateWorkerService;

/**
 * Employees -> Worker info: create a new worker of the company.
 * Opened in the global modal (config/global-modal.php, key "create-worker").
 *
 * Needs the ACL permission employees.worker-info.create (checked again on create).
 * When a worker with the same name exists, a warning is shown first and the user can create anyway.
 * After the create the worker list is refreshed and the new worker selected (WorkerInfo::CREATED_EVENT).
 */
class CreateWorkerModal extends LivewireController
{
    public const PERMISSION = 'employees.worker-info.create';

    /**Params passed in from the global modal (see config/global-modal.php), none needed */
    public array $params = [];

    /**The form, field names of CreateWorkerService::FIELDS */
    public array $form = [
        'name'      => '',
        'surname'   => '',
        'type'      => 'WRK',
        'oib'       => '',
        'workplace' => '',
        'doe'       => '',
        'ced'       => '',
        'phone'     => '',
        'email'     => '',
        'street'    => '',
        'town'      => '',
        'zip'       => '',
        'county'    => '',
    ];

    /**Workers with the same name, shown in the warning */
    public array $sameName = [];

    public function mount()
    {
        $this->form['doe'] = now()->format('Y-m-d');
    }

    private function allowed(): bool
    {
        return AclService::current()->can(self::PERMISSION);
    }

    public function updatedForm($value, $field): void
    {
        /**The warning was for the old name */
        if (in_array($field, ['name', 'surname'], TRUE)) $this->sameName = [];
    }

    /**
     * Check for a worker with the same name first, then create.
     */
    public function confirmBtn()
    {
        if (!$this->allowed()) return $this->showException(translator('You are not allowed to create workers!'));

        $this->sameName = CreateWorkerService::sameName($this->form['name'], $this->form['surname']);
        if (!empty($this->sameName)) return;

        $this->createWorker();
    }

    /**
     * Create the worker even though a worker with the same name exists.
     */
    public function createAnyway()
    {
        if (!$this->allowed()) return $this->showException(translator('You are not allowed to create workers!'));
        $this->createWorker();
    }

    private function createWorker()
    {
        $service = CreateWorkerService::init()->execute($this->form);
        $response = $service->getResponse();
        if (!$service->getResponseStatus()) return $this->showException($response['message']);

        $this->closeGlobalModal();
        $this->dispatch(\App\Livewire\Modules\Employees\WorkerInfo::CREATED_EVENT, id: $response['data']->id);
        $this->notifyMe($response['message']);
    }

    /**
     * Close the global modal without modifying its shared component.
     * The global modal already closes on Escape (see global-modal.blade.php).
     */
    private function closeGlobalModal(): void
    {
        $this->js("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))");
    }

    public function render()
    {
        return view('livewire.modules.employees.components.create-worker-modal', [
            'allowed'    => $this->allowed(),
            'types'      => array_map(fn ($label) => translator($label), CreateWorkerService::types()),
            'workplaces' => Workplace::where('status', TRUE)->orderBy('key')->get(['id', 'key', 'name']),
            'statuses'   => WorkerStatus::WORKER_STATUS_DESCRIPTION,
        ]);
    }
}
