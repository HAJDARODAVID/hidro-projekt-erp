<?php

namespace App\Livewire\Modules\Admin;

use App\Livewire\LivewireController;
use App\Models\User;
use App\Services\Admin\ManageUsersService;
use App\Services\Application\Acl\AclService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Administration - Users: the app users.
 * A table (search, filters, sorting, pagination). A click on a user (or "New user") opens the
 * global modal admin-user (Components\UserModal): details, linked worker, roles, password and status.
 */
class Users extends LivewireController
{
    use WithPagination;

    /**ACL permissions (the route itself is bound to admin.users.view in /app/acl) */
    public const PERMISSION_VIEW     = 'admin.users.view';
    public const PERMISSION_CREATE   = 'admin.users.create';
    public const PERMISSION_EDIT     = 'admin.users.edit';
    public const PERMISSION_STATUS   = 'admin.users.status';
    public const PERMISSION_PASSWORD = 'admin.users.password';
    public const PERMISSION_ROLES    = 'admin.users.roles';

    public const ROUTE = 'getUsersModule';

    /**Global modal of a user (config/global-modal.php) */
    public const MODAL = 'admin-user';

    /**Dispatched by the modal after a change, the table re-renders */
    public const REFRESH_EVENT = 'refresh-admin-users';

    public const PER_PAGE_OPTIONS = [25, 50, 100];
    public const STATUSES = ['all', 'active', 'inactive'];
    public const LINKS = ['', 'worker', 'coop', 'none'];
    public const SORTS = ['name', 'email', 'type', 'last_login_at', 'created_at'];

    #[Url('search')]
    public string $search = '';

    #[Url('status')]
    public string $status = 'active';

    #[Url('type')]
    public string $type = '';

    #[Url('link')]
    public string $link = '';

    #[Url('sort')]
    public string $sort = 'name';

    #[Url('dir')]
    public string $dir = 'asc';

    #[Url('per_page')]
    public int $perPage = 25;

    /**The user last opened in the modal (row highlight) */
    public $openedId = NULL;

    /**
     * Livewire requests skip the route middleware: check the access on every request.
     */
    public function boot()
    {
        abort_unless(AclService::current()->canRoute(self::ROUTE), 403);
    }

    public function mount()
    {
        $this->sanitizeFilters();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'status', 'type', 'link', 'perPage'], TRUE)) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    private function sanitizeFilters(): void
    {
        if (!in_array($this->status, self::STATUSES, TRUE)) $this->status = 'active';
        if ($this->type !== '' && !array_key_exists((int) $this->type, User::USER_TYPE)) $this->type = '';
        if (!in_array($this->link, self::LINKS, TRUE)) $this->link = '';
        if (!in_array($this->sort, self::SORTS, TRUE)) $this->sort = 'name';
        if (!in_array($this->dir, ['asc', 'desc'], TRUE)) $this->dir = 'asc';
        if (!in_array($this->perPage, self::PER_PAGE_OPTIONS, TRUE)) $this->perPage = self::PER_PAGE_OPTIONS[0];
    }

    public function resetFilters()
    {
        $this->reset('search', 'status', 'type', 'link');
        $this->resetPage();
    }

    public function sortBy($column)
    {
        if (!in_array($column, self::SORTS, TRUE)) return;
        $this->dir = $this->sort === $column && $this->dir === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function openUser($id)
    {
        if (!User::whereKey((int) $id)->exists()) return;
        $this->openedId = (int) $id;
        $this->dispatch('open-global-modal', component: self::MODAL, params: ['userId' => (int) $id]);
    }

    public function newUser()
    {
        if (!AclService::current()->can(self::PERMISSION_CREATE)) return $this->showException(translator('You are not allowed to do this!'));
        $this->openedId = NULL;
        $this->dispatch('open-global-modal', component: self::MODAL, params: []);
    }

    /**A change in the modal: render the table again */
    #[On(self::REFRESH_EVENT)]
    public function refreshUsers()
    {
    }

    public function render()
    {
        $users = User::query()
            ->leftJoin('workers', 'workers.id', '=', 'users.worker_id')
            ->leftJoin('cooperator_workers', 'cooperator_workers.id', '=', 'users.coop_id')
            ->leftJoin('cooperators', 'cooperators.id', '=', 'cooperator_workers.cooperator_id')
            ->select('users.*')
            ->selectRaw("TRIM(CONCAT(COALESCE(workers.firstName, ''), ' ', COALESCE(workers.lastName, ''))) as worker_name")
            ->selectRaw("TRIM(CONCAT(COALESCE(cooperator_workers.firstName, ''), ' ', COALESCE(cooperator_workers.lastName, ''))) as coop_name")
            ->selectRaw('cooperators.name as coop_company')
            ->when($this->search !== '', function ($q) {
                $like = '%' . addcslashes(trim($this->search), '%_\\') . '%';
                $q->where(fn ($q) => $q->where('users.name', 'LIKE', $like)
                    ->orWhere('users.last_name', 'LIKE', $like)
                    ->orWhere('users.email', 'LIKE', $like)
                    ->orWhereRaw("CONCAT(users.name, ' ', COALESCE(users.last_name, '')) LIKE ?", [$like]));
            })
            ->when($this->status === 'active', fn ($q) => $q->where('users.active', TRUE))
            ->when($this->status === 'inactive', fn ($q) => $q->where('users.active', FALSE))
            ->when($this->type !== '', fn ($q) => $q->where('users.type', (int) $this->type))
            ->when($this->link === 'worker', fn ($q) => $q->whereNotNull('users.worker_id'))
            ->when($this->link === 'coop', fn ($q) => $q->whereNotNull('users.coop_id'))
            ->when($this->link === 'none', fn ($q) => $q->whereNull('users.worker_id')->whereNull('users.coop_id'))
            ->when($this->sort === 'name', fn ($q) => $q->orderBy('users.name', $this->dir)->orderBy('users.last_name', $this->dir))
            ->when($this->sort === 'last_login_at', fn ($q) => $q->orderByRaw('users.last_login_at IS NULL')->orderBy('users.last_login_at', $this->dir))
            ->when(!in_array($this->sort, ['name', 'last_login_at'], TRUE), fn ($q) => $q->orderBy('users.' . $this->sort, $this->dir))
            ->orderBy('users.id')
            ->paginate($this->perPage);

        $roleNames = DB::table('acl_user_role')
            ->join('acl_roles', 'acl_roles.id', '=', 'acl_user_role.role_id')
            ->whereIn('acl_user_role.user_id', $users->pluck('id'))
            ->orderBy('acl_roles.name')
            ->get(['acl_user_role.user_id', 'acl_roles.name'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('name')->all())
            ->all();

        $acl = AclService::current();

        return view('livewire.modules.admin.users', [
            'users'          => $users,
            'roleNames'      => $roleNames,
            'typeLabels'     => ManageUsersService::typeLabels(),
            'statuses'       => ['all' => translator('All'), 'active' => translator('Active'), 'inactive' => translator('Inactive')],
            'links'          => ['' => translator('Any link'), 'worker' => translator('Linked to a worker'), 'coop' => translator('Linked to a subcontractor'), 'none' => translator('Not linked')],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'counts'         => ['active' => User::where('active', TRUE)->count(), 'inactive' => User::where('active', FALSE)->count()],
            'can'            => ['create' => $acl->can(self::PERMISSION_CREATE), 'acl' => $acl->isSuperAdmin()],
        ]);
    }
}
