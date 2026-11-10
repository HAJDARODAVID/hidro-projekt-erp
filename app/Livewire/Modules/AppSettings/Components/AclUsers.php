<?php

namespace App\Livewire\Modules\AppSettings\Components;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Livewire\LivewireController;
use App\Models\Application\Acl\AclRole;
use App\Services\Application\Acl\AclService;
use App\Services\Application\Acl\ManageAclService;

/**
 * ACL -> User access: give a user roles, per-permission overrides (allow / deny),
 * and see what the user effectively may do.
 */
class AclUsers extends LivewireController
{
    public $userSearch = NULL;

    /**Users for the list on the left */
    public $users = [];

    public $selectedUser = NULL;

    /**Roles of the selected user [role ID => bool] */
    public $userRoles = [];

    /**Overrides of the selected user [permission ID => 'inherit' | 'allow' | 'deny'] */
    public $overrides = [];

    public $permissionSearch = NULL;

    public function mount()
    {
        $this->getUsers();
    }

    private function getUsers(): void
    {
        $this->users = User::query()
            ->when($this->userSearch, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'LIKE', '%' . $this->userSearch . '%')
                ->orWhere('last_name', 'LIKE', '%' . $this->userSearch . '%')
                ->orWhere('email', 'LIKE', '%' . $this->userSearch . '%')))
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'active'])
            ->toArray();
    }

    public function updatedUserSearch($value): void
    {
        if ($value === '') $this->userSearch = NULL;
        $this->getUsers();
    }

    public function resetUserSearch(): void
    {
        $this->reset('userSearch');
        $this->getUsers();
    }

    public function selectUser($id): void
    {
        if ((string) $this->selectedUser === (string) $id) {
            $this->reset('selectedUser', 'userRoles', 'overrides');
            return;
        }
        if (!User::whereKey($id)->exists()) return;

        $this->selectedUser = (int) $id;
        $this->loadUserAccess();
    }

    private function loadUserAccess(): void
    {
        $assigned = DB::table('acl_user_role')->where('user_id', $this->selectedUser)->pluck('role_id')->all();
        $this->userRoles = AclRole::pluck('id')->mapWithKeys(fn ($id) => [$id => in_array($id, $assigned, TRUE)])->all();

        $this->overrides = DB::table('acl_user_permission')
            ->where('user_id', $this->selectedUser)
            ->pluck('allow', 'permission_id')
            ->map(fn ($allow) => $allow ? 'allow' : 'deny')
            ->all();
    }

    public function updatedUserRoles($value, $roleId)
    {
        if (!$this->selectedUser) return;

        try {
            ManageAclService::setUserRole($this->selectedUser, $roleId, (bool) $value);
        } catch (\Throwable $th) {
            $this->userRoles[$roleId] = !$value;
            return $this->showException($th->getMessage());
        }
        $this->notifyMe(translator('User roles saved!'));
    }

    public function updatedOverrides($value, $permissionId)
    {
        if (!$this->selectedUser) return;
        $allow = match ($value) {
            'allow' => TRUE,
            'deny'  => FALSE,
            default => NULL,
        };

        try {
            ManageAclService::setUserPermission($this->selectedUser, $permissionId, $allow);
        } catch (\Throwable $th) {
            $this->loadUserAccess();
            return $this->showException($th->getMessage());
        }
        if ($allow === NULL) unset($this->overrides[$permissionId]);
        $this->notifyMe(translator('User permission saved!'));
    }

    public function render()
    {
        $data = ['roles' => [], 'catalog' => [], 'fromRoles' => [], 'effective' => [], 'isSuperAdmin' => FALSE];

        if ($this->selectedUser) {
            $acl = AclService::forUser($this->selectedUser);
            $data['isSuperAdmin'] = $acl->isSuperAdmin();
            $data['roles'] = AclRole::orderBy('name')->get(['id', 'key', 'name', 'active']);
            $data['catalog'] = ManageAclService::permissionCatalog($this->permissionSearch);
            $data['effective'] = array_flip($acl->permissions());
            $data['fromRoles'] = DB::table('acl_user_role')
                ->join('acl_roles', 'acl_roles.id', '=', 'acl_user_role.role_id')
                ->join('acl_role_permission', 'acl_role_permission.role_id', '=', 'acl_roles.id')
                ->where('acl_user_role.user_id', $this->selectedUser)
                ->where('acl_roles.active', TRUE)
                ->select('acl_role_permission.permission_id', 'acl_roles.name')
                ->get()
                ->groupBy('permission_id')
                ->map(fn ($rows) => $rows->pluck('name')->implode(', '))
                ->all();
        }

        return view('livewire.modules.app-settings.components.acl-users', $data);
    }
}
