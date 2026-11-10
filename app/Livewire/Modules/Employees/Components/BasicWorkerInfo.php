<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\Locked;
use App\Livewire\LivewireController;
use App\Models\Employees\Workplace;
use App\Services\Employees\GetWorkerInfo;
use App\Services\Application\Acl\AclService;
use App\Services\Employees\UpdateWorkerInfoService;

class BasicWorkerInfo extends LivewireController
{
    /**ACL permission needed to change the basic worker info */
    public const EDIT_PERMISSION = 'employees.worker-info.edit';

    #[Locked]
    public $workerId;

    /**Can the user change the fields? Only for the view, the server checks again on every change. */
    #[Locked]
    public bool $canEdit = FALSE;

    /**
     * Store worker info.
     *
     * @var array
     */
    public $workerInfo;

    /**
     * Workplace options [name => name]: the active workplaces, plus the worker's current one
     * if it isn't active (any more), so it still shows. The name is what the worker stores.
     *
     * @var array
     */
    public $workplaces;

    public function mount()
    {
        $this->canEdit = AclService::current()->can(self::EDIT_PERMISSION);
        $this->getWorkerInfo();
        $this->setWorkplaceOptions();
    }

    /**
     * Get all the worker info
     *
     * @return void
     */
    private function getWorkerInfo(): void
    {
        $this->workerInfo = (new GetWorkerInfo($this->workerId))->toArray();
    }

    private function setWorkplaceOptions(): void
    {
        $this->workplaces = ['' => '—'] + Workplace::where('status', TRUE)->orderBy('name')->pluck('name', 'name')->toArray();

        $current = $this->workerInfo['workplace'] ?? NULL;
        if ($current !== NULL && $current !== '' && !array_key_exists($current, $this->workplaces)) {
            $this->workplaces[$current] = $current;
        }
    }

    /**
     * Runs after a worker info field changed (on blur, selects and dates on change).
     * Checks the permission, saves the field and marks it saved (is-valid) or not (is-invalid).
     * Disabled inputs can be bypassed in the browser, so the permission is checked here again.
     *
     * @param mixed $value
     * @param string $field
     * @return void
     */
    public function updatedWorkerInfo($value, $field)
    {
        $this->saved = [];
        $savedKey = 'workerInfo.' . $field;

        if (!AclService::current()->can(self::EDIT_PERMISSION)) {
            $this->getWorkerInfo();
            return $this->showException(translator('You are not allowed to change the worker info!'));
        }

        /**Read only fields (status) are reset, they are changed elsewhere */
        if (!array_key_exists($field, UpdateWorkerInfoService::FIELDS)) {
            $this->getWorkerInfo();
            return;
        }

        $service = (new UpdateWorkerInfoService((int) $this->workerId))->updateField($field, $value);

        if (!$service->getResponseStatus()) {
            $this->saved[$savedKey] = FALSE;
            return $this->showException($service->getResponse()['message']);
        }

        /**Show the value as it was saved (trimmed, OIB without spaces, ...) */
        $this->workerInfo[$field] = $service->getResponse()['data']['value'];
        $this->saved[$savedKey] = TRUE;

        /**The name is shown in the worker list and the card title of the parent */
        if (in_array($field, ['name', 'surname'], TRUE)) $this->dispatch('worker-info-name-changed');
    }

    public function render()
    {
        return view('livewire.modules.employees.components.basic-worker-info');
    }
}
