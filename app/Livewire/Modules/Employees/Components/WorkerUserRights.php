<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Models\User;
use App\Models\UserRole;
use App\Models\RoleGroup;
use App\Models\Employees\Worker;
use App\Livewire\LivewireController;

class WorkerUserRights extends LivewireController
{
    /**Right needed to assign roles to users */
    public const ASSIGN_ROLES_RIGHT = 'can-assign-roles';

    public $workerId;

    /**ID of the app user linked to the worker, NULL when the worker has none */
    public $userId = NULL;

    /**All the assignable role groups [id => name] */
    public $roleGroups = [];

    /**Roles of the user [roleId => bool] */
    public $userRoles = [];

    public function mount()
    {
        $this->userId = User::where('worker_id', $this->workerId)->value('id');
        $this->roleGroups = RoleGroup::orderBy('name')->pluck('name', 'id')->toArray();

        if ($this->userId) {
            $assigned = UserRole::where('user_id', $this->userId)->pluck('role_id')->toArray();
            foreach ($this->roleGroups as $roleId => $name) {
                $this->userRoles[$roleId] = in_array($roleId, $assigned);
            }
        }
    }

    /**
     * Run when a role checkbox is toggled. Adds or removes the role of the user
     * and flags the user so their rights get reloaded on the next request.
     *
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function updated($property, $value)
    {
        if (!str_starts_with($property, 'userRoles.')) return;
        $roleId = (int) substr($property, strlen('userRoles.'));

        if (!$this->userId || !app('user_rights')->hasRight(self::ASSIGN_ROLES_RIGHT) || !array_key_exists($roleId, $this->roleGroups)) {
            $this->userRoles[$roleId] = !$value;
            return $this->showException(translator('You are not allowed to change the rights of this user!'));
        }

        try {
            if ($value) {
                UserRole::firstOrCreate(['user_id' => $this->userId, 'role_id' => $roleId]);
            } else {
                UserRole::where('user_id', $this->userId)->where('role_id', $roleId)->delete();
            }
            User::where('id', $this->userId)->update(['inv_update' => TRUE]);
        } catch (\Throwable $th) {
            $this->userRoles[$roleId] = !$value;
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('User rights for: ') . Worker::find($this->workerId)->fullName . translator(', successfully saved!.'));
    }

    public function render()
    {
        return view('livewire.modules.employees.components.worker-user-rights');
    }
}
