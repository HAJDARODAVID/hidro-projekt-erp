<?php

namespace App\Livewire\Modules\AppSettings\Components;

use App\Livewire\LivewireController;
use App\Models\Application\AppModule;
use App\Models\Application\AppModuleRoute;
use App\Models\Application\Acl\AclPermission;
use App\Services\Application\Acl\ManageAclService;

/**
 * ACL -> Permissions: create / edit / delete permissions and bind them to a module route.
 * A permission bound to a route guards that route (acl middleware) and hides its tab link.
 */
class AclPermissions extends LivewireController
{
    public $permissionSearch = NULL;

    public $moduleFilter = '';

    /**Form of the new permission */
    public $newPermission = ['key' => '', 'name' => '', 'description' => '', 'module_id' => '', 'route_id' => ''];

    /**Editable permissions [permission ID => fields] */
    public $permissions = [];

    /**[module ID => name] */
    public $moduleOptions = [];

    /**[route ID => label] */
    public $routeOptions = [];

    public function mount()
    {
        $this->moduleOptions = ['' => '—'] + AppModule::orderBy('name')->pluck('name', 'id')->all();

        $this->routeOptions = ['' => '—'];
        foreach (AppModuleRoute::orderBy('module_id')->orderBy('position')->get(['id', 'title', 'route_name', 'module_id']) as $route) {
            $module = $this->moduleOptions[$route->module_id] ?? '?';
            $this->routeOptions[$route->id] = $module . ' / ' . ($route->title ?: $route->route_name) . ' (' . $route->route_name . ')';
        }

        $this->getPermissions();
    }

    private function getPermissions(): void
    {
        $this->permissions = AclPermission::query()
            ->when($this->permissionSearch, fn ($q) => $q->where(fn ($q) => $q->where('key', 'LIKE', '%' . $this->permissionSearch . '%')->orWhere('name', 'LIKE', '%' . $this->permissionSearch . '%')))
            ->when($this->moduleFilter === '-', fn ($q) => $q->whereNull('module_id'))
            ->when($this->moduleFilter !== '' && $this->moduleFilter !== '-', fn ($q) => $q->where('module_id', $this->moduleFilter))
            ->withCount('roles')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (AclPermission $p) => [$p->id => [
                'key'         => $p->key,
                'name'        => $p->name,
                'description' => $p->description,
                'module_id'   => $p->module_id ?? '',
                'route_id'    => $p->route_id ?? '',
                'roles_count' => $p->roles_count,
            ]])
            ->all();
    }

    public function updatedPermissionSearch($value): void
    {
        if ($value === '') $this->permissionSearch = NULL;
        $this->getPermissions();
    }

    public function updatedModuleFilter(): void
    {
        $this->getPermissions();
    }

    public function updatedNewPermission($value, $field): void
    {
        if ($field === 'key') $this->newPermission['key'] = strtolower(trim((string) $value));
    }

    public function createPermission()
    {
        try {
            $permission = ManageAclService::createPermission(
                (string) $this->newPermission['key'],
                (string) $this->newPermission['name'],
                $this->newPermission['description'] ?: NULL,
                $this->newPermission['module_id'] ?: NULL,
                $this->newPermission['route_id'] ?: NULL,
            );
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->reset('newPermission');
        $this->getPermissions();
        $this->notifyMe(translator('Permission created') . ': ' . $permission->key);
    }

    /**
     * Save a changed field of a permission (name, description, module, route).
     */
    public function updatedPermissions($value, $path)
    {
        [$permissionId, $field] = array_pad(explode('.', $path, 2), 2, NULL);
        if (!in_array($field, ['name', 'description', 'module_id', 'route_id'], TRUE)) return;

        try {
            ManageAclService::updatePermission($permissionId, [$field => $value]);
        } catch (\Throwable $th) {
            $this->getPermissions();
            return $this->showException($th->getMessage());
        }

        $this->getPermissions();
        $this->notifyMe(translator('Permission saved!'));
    }

    public function deletePermission($permissionId)
    {
        try {
            ManageAclService::deletePermission($permissionId);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->getPermissions();
        $this->notifyMe(translator('Permission deleted!'));
    }

    public function render()
    {
        return view('livewire.modules.app-settings.components.acl-permissions');
    }
}
