<?php

namespace App\Livewire\Modules\Admin\Components;

use App\Livewire\Modules\Admin\Users;
use App\Livewire\Tables\AppDataTable;
use App\Models\User;
use App\Services\Admin\ManageUsersService;
use App\Services\Application\Acl\AclService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * Administration - Users: the table of the app users (AppDataTable).
 * A click on a row opens the user in the global modal admin-user (UserModal); "New user" opens it empty.
 */
class UsersTable extends AppDataTable
{
    protected $model = User::class;

    /**A click on a row opens the user in the global modal */
    protected ?string $rowModal = Users::MODAL;

    protected string $rowModalParam = 'userId';

    /**
     * Livewire requests skip the route middleware: check the access on every request.
     */
    public function boot(): void
    {
        abort_unless(AclService::current()->canRoute(Users::ROUTE), 403);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setFitToScreenEnabled()
            ->setAdditionalSelects([
                'users.id as id', // the primary key is not a column of the table: select it (row click, highlight, "You")
                'users.worker_id',
                'users.coop_id',
                'users.last_name',
                DB::raw("TRIM(CONCAT(COALESCE(workers.firstName, ''), ' ', COALESCE(workers.lastName, ''))) as worker_name"),
                DB::raw("TRIM(CONCAT(COALESCE(cooperator_workers.firstName, ''), ' ', COALESCE(cooperator_workers.lastName, ''))) as coop_name"),
                DB::raw('cooperators.name as coop_company'),
                DB::raw("(SELECT GROUP_CONCAT(acl_roles.name ORDER BY acl_roles.name SEPARATOR ', ') FROM acl_user_role
                    JOIN acl_roles ON acl_roles.id = acl_user_role.role_id WHERE acl_user_role.user_id = users.id) as role_names"),
            ]);

        $counts = ['active' => User::where('active', TRUE)->count(), 'inactive' => User::where('active', FALSE)->count()];
        $this->toolbarView('toolbar-left-start', 'livewire.modules.admin.components.users-table-counts', ['counts' => $counts]);

        $this->toolbarButtons([
            [
                'icon'    => 'shield-lock',
                'title'   => translator('Access control'),
                'route'   => 'getAclModule',
                'visible' => AclService::current()->isSuperAdmin(),
            ],
            [
                'icon'  => 'person-plus',
                'title' => translator('New user'),
                'type'  => 'pri',
                'modal' => Users::MODAL,
                'can'   => Users::PERMISSION_CREATE,
            ],
        ], 'toolbar-right-end');
    }

    public function builder(): Builder
    {
        return User::query()
            ->leftJoin('workers', 'workers.id', '=', 'users.worker_id')
            ->leftJoin('cooperator_workers', 'cooperator_workers.id', '=', 'users.coop_id')
            ->leftJoin('cooperators', 'cooperators.id', '=', 'cooperator_workers.cooperator_id');
    }

    public function columns(): array
    {
        $typeLabels = ManageUsersService::typeLabels();
        $me = auth()->id();

        return [
            Column::make(translator('Name'), 'name')
                ->sortable(fn (Builder $q, string $dir) => $q->orderBy('users.name', $dir)->orderBy('users.last_name', $dir))
                ->searchable(fn (Builder $q, $term) => $q
                    ->orWhere('users.name', 'LIKE', '%' . $term . '%')
                    ->orWhere('users.last_name', 'LIKE', '%' . $term . '%')
                    ->orWhereRaw("CONCAT(users.name, ' ', COALESCE(users.last_name, '')) LIKE ?", ['%' . $term . '%']))
                ->format(fn ($value, $row) => new HtmlString(e(trim($value . ' ' . $row->last_name))
                    . ($row->id === $me ? ' <span class="badge text-bg-light border no-border-radius">' . e(translator('You')) . '</span>' : '')))
                ->html(),
            Column::make(translator('Email'), 'email')
                ->sortable()
                ->searchable(),
            Column::make(translator('User type'), 'type')
                ->sortable()
                ->format(fn ($value) => $typeLabels[$value] ?? $value),
            Column::make(translator('Linked worker'))
                ->label(fn ($row) => $this->linkedWorker($row))
                ->html(),
            Column::make(translator('Roles'))
                ->label(fn ($row) => e((string) $row->role_names)),
            $this->dateColumn(translator('Last login'), 'last_login_at', 'd.m.Y H:i')
                ->sortable(fn (Builder $q, string $dir) => $q->orderByRaw('users.last_login_at IS NULL')->orderBy('users.last_login_at', $dir)),
            $this->statusColumn(translator('Status'), 'active'),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make(translator('Status'), 'status')
                ->options(['' => translator('All'), 'active' => translator('Active'), 'inactive' => translator('Inactive')])
                ->filter(fn (Builder $q, $value) => match ($value) {
                    'active'   => $q->where('users.active', TRUE),
                    'inactive' => $q->where('users.active', FALSE),
                    default    => $q,
                }),
            SelectFilter::make(translator('User type'), 'type')
                ->options(['' => translator('All types')] + ManageUsersService::typeLabels())
                ->filter(fn (Builder $q, $value) => $q->where('users.type', (int) $value)),
            SelectFilter::make(translator('Linked worker'), 'link')
                ->options([
                    ''       => translator('Any link'),
                    'worker' => translator('Linked to a worker'),
                    'coop'   => translator('Linked to a subcontractor'),
                    'none'   => translator('Not linked'),
                ])
                ->filter(fn (Builder $q, $value) => match ($value) {
                    'worker' => $q->whereNotNull('users.worker_id'),
                    'coop'   => $q->whereNotNull('users.coop_id'),
                    'none'   => $q->whereNull('users.worker_id')->whereNull('users.coop_id'),
                    default  => $q,
                }),
        ];
    }

    private function linkedWorker($row): string
    {
        if ($row->worker_id) {
            return '<i class="bi bi-person-badge" title="' . e(translator('Worker')) . '"></i> ' . e($row->worker_name ?: '#' . $row->worker_id);
        }
        if ($row->coop_id) {
            return '<i class="bi bi-building" title="' . e(translator('Subcontractor')) . '"></i> ' . e($row->coop_name ?: '#' . $row->coop_id)
                . ($row->coop_company ? ' <span class="text-muted">(' . e($row->coop_company) . ')</span>' : '');
        }
        return '';
    }

    /**A change in the user modal: render the table again */
    #[On(Users::REFRESH_EVENT)]
    public function refreshUsers(): void
    {
    }
}
