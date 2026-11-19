<?php

namespace App\Livewire\Modules\Admin;

use App\Livewire\LivewireController;
use App\Services\Application\Acl\AclService;

/**
 * Administration - Users: the module page.
 * The table is Components\UsersTable (AppDataTable); a user is opened in the global modal admin-user (Components\UserModal).
 */
class Users extends LivewireController
{
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

    /**Dispatched by the modal after a change, the users table re-renders */
    public const REFRESH_EVENT = 'refresh-admin-users';

    /**
     * Livewire requests skip the route middleware: check the access on every request.
     */
    public function boot()
    {
        abort_unless(AclService::current()->canRoute(self::ROUTE), 403);
    }

    public function render()
    {
        return view('livewire.modules.admin.users');
    }
}
