<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use App\Services\Employees\GetWorkerInfo;
use App\Services\Application\Acl\AclService;
use Livewire\Attributes\Locked;

class BasicWorkerInfo extends LivewireController
{
    /**ACL permission needed to change the basic worker info */
    public const EDIT_PERMISSION = 'employees.worker-info.edit';

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
     * Store all the active workplaces.
     *
     * @var array  
     */
    public $workplaces;

    public function mount()
    {
        $this->canEdit = AclService::current()->can(self::EDIT_PERMISSION);
        $this->getWorkerInfo();
        $this->workplaces = Workplace::where('status', TRUE)->pluck('name', 'id')->toArray();
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

    /**
     * Runs after a worker info field changed. Disabled inputs can be bypassed in the browser,
     * so the permission is checked again here; without it the change is thrown away.
     */
    public function updatedWorkerInfo($value, $field)
    {
        if (!AclService::current()->can(self::EDIT_PERMISSION)) {
            $this->getWorkerInfo();
            return $this->showException(translator('You are not allowed to change the worker info!'));
        }

        // TODO: save the changed field
    }

    public function render()
    {
        return view('livewire.modules.employees.components.basic-worker-info');
    }
}
