<?php

namespace App\Livewire\Modules\Admin\Components;

use App\Exceptions\ErrorMessage;
use App\Livewire\LivewireController;
use App\Livewire\Modules\Admin\Users;
use App\Models\Application\Acl\AclRole;
use App\Models\CooperatorWorkersModel;
use App\Models\Employees\Worker;
use App\Models\User;
use App\Services\Admin\ManageUsersService;
use App\Services\Application\Acl\AclService;
use App\Services\Application\Acl\ManageAclService;
use App\Services\Application\AppTranslationsService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

/**
 * Administration - Users: global modal (config/global-modal.php: admin-user) for a new or an existing user.
 * Details, linked worker, roles, password and status. Every action is checked against the ACL.
 * Opened from the users table with params ['userId' => ID] (none = new user).
 */
class UserModal extends LivewireController
{
    /**Params passed in from the global modal */
    public array $params = [];

    /**The user, NULL while creating a new one */
    #[Locked]
    public $userId = NULL;

    public array $form = [];

    /**New password typed in the modal (empty = generate one) */
    public string $customPassword = '';

    /**A password that was just set: shown once, then gone */
    public $shownPassword = NULL;

    /**Roles of the user [role ID => bool] */
    public array $userRoles = [];

    /**
     * Livewire requests skip the route middleware: check the access on every request.
     */
    public function boot()
    {
        abort_unless(AclService::current()->canRoute(Users::ROUTE), 403);
    }

    public function mount()
    {
        $id = isset($this->params['userId']) ? (int) $this->params['userId'] : NULL;
        $id && User::whereKey($id)->exists() ? $this->loadUser($id) : $this->newForm();
    }

    private function newForm(): void
    {
        $this->userId = NULL;
        $this->form = [
            'name'      => '',
            'last_name' => '',
            'email'     => '',
            'type'      => (string) User::USER_TYPE_ADMIN_STAFF,
            'lang'      => AppTranslationsService::defaultLanguage(),
            'link'      => '',
        ];
        $this->loadUserRoles();
    }

    private function loadUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->form = [
            'name'      => (string) $user->name,
            'last_name' => (string) $user->last_name,
            'email'     => (string) $user->email,
            'type'      => (string) $user->type,
            'lang'      => (string) ($user->lang ?: AppTranslationsService::defaultLanguage()),
            'link'      => $user->worker_id ? 'worker:' . $user->worker_id : ($user->coop_id ? 'coop:' . $user->coop_id : ''),
        ];
        $this->loadUserRoles();
    }

    /**
     * Fill the name from the linked worker (only empty fields).
     */
    public function updatedFormLink($value)
    {
        if (!preg_match('/^(worker|coop):(\d+)$/', (string) $value, $match)) return;
        $person = $match[1] === 'worker' ? Worker::find((int) $match[2]) : CooperatorWorkersModel::find((int) $match[2]);
        if (!$person) return;
        if (trim($this->form['name'] ?? '') === '') $this->form['name'] = (string) $person->firstName;
        if (trim($this->form['last_name'] ?? '') === '') $this->form['last_name'] = (string) $person->lastName;
    }

    public function save()
    {
        $service = ManageUsersService::asCurrentUser();

        try {
            if (!$this->userId) {
                if (!$this->can(Users::PERMISSION_CREATE)) return $this->denied();
                [$user, $password] = DB::transaction(function () use ($service) {
                    [$user, $password] = $service->create($this->form + ['password' => $this->customPassword]);
                    $this->saveRoles($user->id);
                    return [$user, $password];
                });
                // the modal stays open on the new user, with the password shown once
                $this->loadUser($user->id);
                $this->customPassword = '';
                $this->shownPassword = $password;
                $this->changed(translator('User created!'));
                return;
            }

            if (!$this->can(Users::PERMISSION_EDIT)) return $this->denied();
            $service->update($this->user(), $this->form);
        } catch (ErrorMessage $e) {
            return $this->showException($e->getMessage());
        }

        $this->loadUser($this->userId);
        $this->changed(translator('User saved!'));
    }

    /**Undo the unsaved changes of the form */
    public function resetForm()
    {
        $this->userId ? $this->loadUser($this->userId) : $this->newForm();
    }

    public function setActive($active)
    {
        if (!$this->userId || !$this->can(Users::PERMISSION_STATUS)) return $this->denied();

        try {
            ManageUsersService::asCurrentUser()->setActive($this->user(), (bool) $active);
        } catch (ErrorMessage $e) {
            return $this->showException($e->getMessage());
        }
        $this->changed($active ? translator('User activated!') : translator('User deactivated, they are signed out on their next request.'));
    }

    public function resetPassword()
    {
        if (!$this->userId || !$this->can(Users::PERMISSION_PASSWORD)) return $this->denied();

        try {
            $this->shownPassword = ManageUsersService::asCurrentUser()->resetPassword($this->user(), $this->customPassword);
        } catch (ErrorMessage $e) {
            return $this->showException($e->getMessage());
        }
        $this->customPassword = '';
        $this->changed(translator('New password set!'));
    }

    public function hidePassword()
    {
        $this->shownPassword = NULL;
    }

    /**
     * A role checkbox of an existing user: saved right away. For a new user the roles are saved with the user.
     */
    public function updatedUserRoles($value, $roleId)
    {
        if (!$this->userId) return;
        if (!$this->can(Users::PERMISSION_ROLES) || !AclRole::whereKey($roleId)->exists() || !ManageUsersService::asCurrentUser()->canManage($this->user())) {
            $this->loadUserRoles();
            return $this->denied();
        }

        try {
            ManageAclService::setUserRole($this->userId, $roleId, (bool) $value);
        } catch (\Throwable $th) {
            $this->loadUserRoles();
            return $this->showException($th->getMessage());
        }
        $this->changed(translator('User roles saved!'));
    }

    private function saveRoles(int $userId): void
    {
        if (!$this->can(Users::PERMISSION_ROLES)) return;
        $roleIds = AclRole::pluck('id')->all();
        foreach ($this->userRoles as $roleId => $assigned) {
            if ($assigned && in_array($roleId, $roleIds, TRUE)) ManageAclService::setUserRole($userId, $roleId, TRUE);
        }
    }

    private function loadUserRoles(): void
    {
        $assigned = $this->userId ? DB::table('acl_user_role')->where('user_id', $this->userId)->pluck('role_id')->all() : [];
        $this->userRoles = AclRole::pluck('id')->mapWithKeys(fn ($id) => [$id => in_array($id, $assigned, TRUE)])->all();
    }

    /** Notify and refresh the users table behind the modal */
    private function changed(string $message): void
    {
        $this->notifyMe($message);
        $this->dispatch(Users::REFRESH_EVENT)->to(Users::class);
    }

    private function user(): User
    {
        return User::findOrFail($this->userId);
    }

    private function can(string $permission): bool
    {
        return AclService::current()->can($permission);
    }

    private function denied()
    {
        return $this->showException(translator('You are not allowed to do this!'));
    }

    /**
     * Workers and subcontractor workers that can be linked: active and not linked to another user (plus the current link).
     */
    private function linkOptions(): array
    {
        $current = $this->form['link'] ?? '';
        $takenWorkers = User::whereNotNull('worker_id')->when($this->userId, fn ($q) => $q->whereKeyNot($this->userId))->pluck('worker_id')->all();
        $takenCoops = User::whereNotNull('coop_id')->when($this->userId, fn ($q) => $q->whereKeyNot($this->userId))->pluck('coop_id')->all();

        $workers = Worker::query()
            ->where(fn ($q) => $q->where('status', 1)->when(str_starts_with($current, 'worker:'), fn ($q) => $q->orWhere('id', (int) substr($current, 7))))
            ->whereNotIn('id', $takenWorkers)
            ->orderBy('firstName')->orderBy('lastName')
            ->get(['id', 'firstName', 'lastName'])
            ->mapWithKeys(fn ($w) => ['worker:' . $w->id => $w->firstName . ' ' . $w->lastName])
            ->all();

        $coops = CooperatorWorkersModel::query()
            ->leftJoin('cooperators', 'cooperators.id', '=', 'cooperator_workers.cooperator_id')
            ->where(fn ($q) => $q->where('cooperator_workers.status', CooperatorWorkersModel::COOPERATORS_WORKER_STATUS_ACTIVE)
                ->when(str_starts_with($current, 'coop:'), fn ($q) => $q->orWhere('cooperator_workers.id', (int) substr($current, 5))))
            ->whereNotIn('cooperator_workers.id', $takenCoops)
            ->orderBy('cooperator_workers.firstName')->orderBy('cooperator_workers.lastName')
            ->get(['cooperator_workers.id', 'cooperator_workers.firstName', 'cooperator_workers.lastName', 'cooperators.name as company'])
            ->mapWithKeys(fn ($w) => ['coop:' . $w->id => $w->firstName . ' ' . $w->lastName . ($w->company ? ' (' . $w->company . ')' : '')])
            ->all();

        return ['workers' => $workers, 'coops' => $coops];
    }

    public function render()
    {
        $acl = AclService::current();
        $service = ManageUsersService::asCurrentUser();
        $user = $this->userId ? User::find($this->userId) : NULL;

        // the types the user may give, plus the current type of the user (shown, e.g. super admin for an admin)
        $assignableTypes = $service->assignableTypes();
        if ($user && !array_key_exists((int) $user->type, $assignableTypes) && isset(User::USER_TYPE[(int) $user->type])) {
            $assignableTypes[(int) $user->type] = translator(User::USER_TYPE[(int) $user->type]);
        }

        return view('livewire.modules.admin.components.user-modal', [
            'user'            => $user,
            'isSuperAdmin'    => $user ? ManageUsersService::isSuperAdmin($user) : FALSE,
            'canManage'       => $user ? $service->canManage($user) : TRUE,
            'isSelf'          => $user && $user->id === auth()->id(),
            'assignableTypes' => $assignableTypes,
            'languages'       => AppTranslationsService::languages(),
            'linkOptions'     => $this->linkOptions(),
            'roles'           => AclRole::orderBy('name')->get(['id', 'key', 'name', 'description', 'active']),
            'can'             => [
                'create'   => $acl->can(Users::PERMISSION_CREATE),
                'edit'     => $acl->can(Users::PERMISSION_EDIT),
                'status'   => $acl->can(Users::PERMISSION_STATUS),
                'password' => $acl->can(Users::PERMISSION_PASSWORD),
                'roles'    => $acl->can(Users::PERMISSION_ROLES),
                'acl'      => $acl->isSuperAdmin(),
            ],
            'minPasswordLength' => ManageUsersService::MIN_PASSWORD_LENGTH,
        ]);
    }
}
