<?php

namespace App\Livewire\Modules\WorkingHours;

use App\Livewire\LivewireController;
use App\Models\Employees\Worker;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;
use Livewire\Attributes\Url;

class Employee extends LivewireController
{
    #[Url('search')]
    public $workerSearch = NULL;

    /**Selected workplace */
    #[Url('worker')]
    public $selectedWorker = NULL;

    /**All active workers */
    public $workers = [];

    public function mount()
    {
        $this->setTabs([
            'monthly-overview' => 'Monthly overview',
        ]);
        $this->getWorkers();
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
        $allowedWorkerTypes = WorkerType::init()->getTypesForAttendance();
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
    }

    public function render()
    {
        return view('livewire.modules.working-hours.employee');
    }
}
