<?php

namespace App\Livewire\Modules\AppSettings\Components;

use App\Livewire\LivewireController;
use App\Services\Application\Settings\EditAppModuleRouteService;
use App\Services\Application\Settings\GetAppModuleRoutesService;

class RoutesBasicInfo extends LivewireController
{
    /**This will be pass thru */
    public $routeID;

    /**All the route info/data */
    public $routeData;

    public function mount()
    {
        $this->getRouteInfo();
    }

    public function updatedRouteData($value, $key)
    {
        /**Strict check so a FALSE from a switch (Exp: divider_after) isn't turned into NULL */
        if ($value === "") $value = NULL;
        $editAppModuleRouteService = app(EditAppModuleRouteService::class);
        $editAppModuleRouteService->ofId($this->routeID)->setNewValue([$key => $value]);
        $editAppModuleRouteService = $editAppModuleRouteService->save();
        if ($editAppModuleRouteService['success']) {
            return $this->dispatch('notify', ['message' => 'Successfully saved!', 'type' => 'success']);
        } else {
            return $this->dispatch('show-exception-modal', $editAppModuleRouteService['error']);
        }
    }

    /**
     * Get all the route info needed
     * 
     * @return void
     */
    private function getRouteInfo(): void
    {
        $this->routeData = GetAppModuleRoutesService::byId($this->routeID)->getAppModuleRouteArray();
    }

    public function render()
    {
        $this->getRouteInfo();
        return view('livewire.modules.app-settings.components.routes-basic-info');
    }
}
