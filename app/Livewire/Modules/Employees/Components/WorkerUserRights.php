<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Models\User;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\DB;
use App\Models\Employees\Worker;
use App\Livewire\LivewireController;
use App\Models\Application\Acl\AclRole;
use App\Services\Application\Acl\AclService;
use App\Services\Application\Acl\ManageAclService;

/**
 * Worker info -> User rights: give the app user of a worker roles of the new ACL (/app/acl).
 */
class WorkerUserRights extends LivewireController
{
    /**ACL permission needed to see the tab and change the roles */
    public const PERMISSION = 'employees.worker-info.user-rights';

    public $workerId;

    /**ID of the app user linked to the worker, NULL when the worker has none */
    #[Locked]
    public $userId = NULL;

    /**Roles of the user [role ID => bool] */
    public $userRoles = [];

    public function mount()
    {
        $this->userId = User::where('worker_id', $this->workerId)->value('id');
        $this->loadUserRoles();
    }

    private function loadUserRoles(): void
    {
        if (!$this->userId) return;

        $assigned = DB::table('acl_user_role')->where('user_id', $this->userId)->pluck('role_id')->all();
        $this->userRoles = AclRole::pluck('id')->mapWithKeys(fn ($id) => [$id => in_array($id, $assigned, TRUE)])->all();
    }

    /**
     * Run when a role checkbox is toggled. Gives or takes the role; the change counts
     * on the user's next request.
     *
     * @param mixed $value
     * @param string $roleId
     * @return void
     */
    public function updatedUserRoles($value, $roleId)
    {
        if (!$this->userId || !AclService::current()->can(self::PERMISSION) || !AclRole::whereKey($roleId)->exists()) {
            $this->loadUserRoles();
            return $this->showException(translator('You are not allowed to change the rights of this user!'));
        }

        try {
            ManageAclService::setUserRole((int) $this->userId, $roleId, (bool) $value);
        } catch (\Throwable $th) {
            $this->loadUserRoles();
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('User rights for: ') . Worker::find($this->workerId)->fullName . translator(', successfully saved!.'));
    }

    public function render()
    {
        $data = ['roles' => collect(), 'effective' => [], 'isSuperAdmin' => FALSE];

        if ($this->userId) {
            $acl = AclService::forUser((int) $this->userId);
            $data['roles'] = AclRole::withCount('permissions')->orderBy('name')->get(['id', 'key', 'name', 'description', 'active']);
            $data['effective'] = $acl->permissions();
            $data['isSuperAdmin'] = $acl->isSuperAdmin();
        }

        return view('livewire.modules.employees.components.worker-user-rights', $data);
    }
}
