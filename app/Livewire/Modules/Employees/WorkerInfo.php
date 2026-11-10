<?php

namespace App\Livewire\Modules\Employees;

use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Models\Employees\Worker;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;
use App\Services\Employees\HasWorkerAppUserService;
use App\Livewire\Modules\Employees\Components\WorkerUserRights;

class WorkerInfo extends LivewireController
{
    /**ACL permission needed to see the payroll tab */
    public const PAYROLL_PERMISSION = 'employees.worker-info.payroll';

    #[Url('search')]
    public $workerSearch = NULL;

    /**Selected workplace */
    #[Url('worker')]
    public $selectedWorker = NULL;

    /**All active workers */
    public $workers = [];

    public function mount()
    {
        $this->setTabs($this->getAvailableTabs());
        $this->getWorkers();
    }

    /**
     * Tabs available for the selected worker. The user rights tab is only shown
     * when the worker has an app user and the logged in user holds the ACL permission
     * (the permission itself is checked by filterTabsByPermission()).
     *
     * @return array
     */
    private function getAvailableTabs(): array
    {
        $tabs = [
            'basic-info' => translator('Basic info'),
            'payroll-info' => ['title' => translator('Payroll'), 'permission' => self::PAYROLL_PERMISSION],
        ];

        if ($this->selectedWorker && HasWorkerAppUserService::byWorkerID((int) $this->selectedWorker)->hasUser()) {
            $tabs['user-right'] = ['title' => translator('User rights'), 'permission' => WorkerUserRights::PERMISSION];
        }

        return $tabs;
    }

    /**
     * Rebuild the tabs for the selected worker, keeping the active tab if it is still available.
     *
     * @return void
     */
    private function refreshTabs(): void
    {
        $this->tabs = $this->filterTabsByPermission($this->getAvailableTabs());
        if (!array_key_exists($this->activeTab, $this->tabs)) $this->activeTab = array_key_first($this->tabs);
    }

    /**
     * Get all workers.
     * This will consider $this->workerSearch property
     */
    private function getWorkers()
    {
        $workers = new Worker();

        if ($this->workerSearch) {
            $workers = $workers->where('firstName', 'LIKE', '%' . $this->workerSearch . '%')->orWhere('lastName', 'LIKE', '%' . $this->workerSearch . '%');
        } else {
            $workers = $workers->whereIn('status', WorkerStatus::init()->areEmployed());
        }
        $workers = $workers->orderBy('id', 'ASC');
        $this->workers = $workers->select('id', 'firstName', 'lastName', 'status', 'type')->get()->toArray();

        /**Exclude worker types */
        $allowedWorkerTypes = WorkerType::init()->getAllType(TRUE);
        foreach ($this->workers as $key => $worker) {
            $this->workers[$key]['indicator'] = WorkerStatus::WORKER_STATUS_INDICATOR[$worker['status']];
            if (!in_array($worker['type'], $allowedWorkerTypes)) unset($this->workers[$key]);
        }
        return;
    }

    /**
     * Runs on the after the property has been updated.
     * This will trigger the getWorkers method
     * 
     * @return void 
     */
    public function updatedWorkerSearch($value): void
    {
        if ($value == "") $this->workerSearch = NULL;
        $this->getWorkers();
    }

    /**
     * Reset the workerSearch property, and trigger the getWorkers method.
     * 
     * @return void
     */
    public function resetWorkerSearchInput(): void
    {
        $this->reset('workerSearch');
        $this->getWorkers();
    }

    /**
     * Select a worker and show more info about him. 
     */
    public function selectWorker($id)
    {
        if ($this->selectedWorker == $id) {
            $this->selectedWorker = NULL;
        } else {
            /**Check if the module exists */
            //$appModule = GetAppModuleService::byId($uuid);
            //$this->selectedWorker = $appModule->exists() ? $uuid : NULL;
            $this->selectedWorker = $id;
        }
        $this->refreshTabs();
    }

    public function render()
    {
        return view('livewire.modules.employees.worker-info');
    }
}
