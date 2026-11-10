<?php

namespace App\Livewire\Modules\AppSettings\Components;

use Illuminate\Support\Facades\DB;
use App\Livewire\LivewireController;
use App\Models\Application\Acl\AclRole;
use App\Services\Application\Acl\ManageAclService;

/**
 * ACL -> Roles: create / edit / delete roles and pick the permissions each role holds.
 */
class AclRoles extends LivewireController
{
    public $roleSearch = NULL;

    /**Roles for the list on the left */
    public $roles = [];

    /**ID of the selected role */
    public $selectedRole = NULL;

    /**Form of the new role */
    public $newRole = ['key' => '', 'name' => ''];

    /**Editable fields of the selected role */
    public $roleForm = [];

    /**Permissions of the selected role [permission ID => bool] */
    public $rolePermissions = [];

    public $permissionSearch = NULL;

    public function mount()
    {
        $this->getRoles();
    }

    private function getRoles(): void
    {
        $this->roles = AclRole::query()
            ->when($this->roleSearch, fn ($q) => $q->where(fn ($q) => $q->where('key', 'LIKE', '%' . $this->roleSearch . '%')->orWhere('name', 'LIKE', '%' . $this->roleSearch . '%')))
            ->withCount('permissions')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'active'])
            ->toArray();

        $userCounts = DB::table('acl_user_role')->select('role_id', DB::raw('COUNT(*) as users'))->groupBy('role_id')->pluck('users', 'role_id');
        foreach ($this->roles as $key => $role) {
            $this->roles[$key]['users_count'] = $userCounts[$role['id']] ?? 0;
        }
    }

    public function updatedRoleSearch($value): void
    {
        if ($value === '') $this->roleSearch = NULL;
        $this->getRoles();
    }

    public function resetRoleSearch(): void
    {
        $this->reset('roleSearch');
        $this->getRoles();
    }

    /**
     * Suggest the key from the name while the key is still empty, and keep the key in the right format.
     */
    public function updatedNewRole($value, $field): void
    {
        if ($field === 'name' && trim((string) $this->newRole['key']) === '') $this->newRole['key'] = ManageAclService::normalizeRoleKey((string) $value);
        if ($field === 'key') $this->newRole['key'] = ManageAclService::normalizeRoleKey((string) $value);
    }

    public function createRole()
    {
        try {
            $role = ManageAclService::createRole((string) $this->newRole['key'], (string) $this->newRole['name']);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->reset('newRole');
        $this->getRoles();
        $this->selectRole($role->id);
        $this->notifyMe(translator('Role created') . ': ' . $role->name);
    }

    public function selectRole($id): void
    {
        if ($this->selectedRole === $id) {
            $this->reset('selectedRole', 'roleForm', 'rolePermissions');
            return;
        }

        $role = AclRole::with('permissions:id')->find($id);
        if ($role === NULL) return;

        $this->selectedRole = $role->id;
        $this->roleForm = ['key' => $role->key, 'name' => $role->name, 'description' => $role->description, 'active' => $role->active];
        $this->rolePermissions = $role->permissions->pluck('id')->mapWithKeys(fn ($id) => [$id => TRUE])->all();
    }

    /**
     * Save a changed field of the selected role (name, description, active).
     */
    public function updatedRoleForm($value, $field)
    {
        if (!$this->selectedRole || !in_array($field, ['name', 'description', 'active'], TRUE)) return;

        try {
            ManageAclService::updateRole($this->selectedRole, [$field => $field === 'active' ? (bool) $value : $value]);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->getRoles();
        $this->notifyMe(translator('Role saved!'));
    }

    /**
     * A permission checkbox of the selected role was toggled.
     */
    public function updatedRolePermissions($value, $permissionId)
    {
        if (!$this->selectedRole) return;

        try {
            ManageAclService::setRolePermission($this->selectedRole, $permissionId, (bool) $value);
        } catch (\Throwable $th) {
            $this->rolePermissions[$permissionId] = !$value;
            return $this->showException($th->getMessage());
        }

        $this->getRoles();
    }

    /**
     * Grant or take away every permission of a module group at once.
     */
    public function setGroupPermissions(string $group, bool $grant)
    {
        if (!$this->selectedRole) return;
        $catalog = ManageAclService::permissionCatalog();
        if (!isset($catalog[$group])) return;

        try {
            foreach (array_keys($catalog[$group]) as $permissionId) {
                ManageAclService::setRolePermission($this->selectedRole, $permissionId, $grant);
                $this->rolePermissions[$permissionId] = $grant;
            }
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->getRoles();
    }

    public function deleteRole()
    {
        if (!$this->selectedRole) return;

        try {
            ManageAclService::deleteRole($this->selectedRole);
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->reset('selectedRole', 'roleForm', 'rolePermissions');
        $this->getRoles();
        $this->notifyMe(translator('Role deleted!'));
    }

    public function render()
    {
        return view('livewire.modules.app-settings.components.acl-roles', [
            'catalog' => $this->selectedRole ? ManageAclService::permissionCatalog($this->permissionSearch) : [],
        ]);
    }
}
