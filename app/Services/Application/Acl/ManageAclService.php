<?php

namespace App\Services\Application\Acl;

use App\Exceptions\ErrorMessage;
use Illuminate\Support\Facades\DB;
use App\Models\Application\Acl\AclRole;
use App\Models\Application\Acl\AclPermission;
use App\Models\Application\AppModuleRoute;

/**
 * Class ManageAclService.
 * All writes to the new ACL go through here, so every change flushes the ACL cache.
 * Validation problems are thrown as ErrorMessage, the caller shows them.
 */
class ManageAclService
{
    /**Permission keys: lowercase words split by dots or dashes, e.g. employees.worker-info.view */
    public const PERMISSION_KEY_PATTERN = '/^[a-z0-9]+(?:[.\-_][a-z0-9]+)*$/';

    /**Role keys: uppercase words split by underscores, e.g. HR_MANAGER */
    public const ROLE_KEY_PATTERN = '/^[A-Z0-9]+(?:_[A-Z0-9]+)*$/';

    /* ---------------------------------------------------------------- Roles */

    public static function createRole(string $key, string $name, ?string $description = NULL): AclRole
    {
        $key = self::normalizeRoleKey($key);
        if (!preg_match(self::ROLE_KEY_PATTERN, $key)) throw new ErrorMessage(translator('The role key may only hold letters, numbers and underscores.'));
        if (trim($name) === '') throw new ErrorMessage(translator('Enter the role name.'));
        if (AclRole::where('key', $key)->exists()) throw new ErrorMessage(translator('A role with this key already exists!'));

        $role = AclRole::create(['key' => $key, 'name' => trim($name), 'description' => $description ?: NULL, 'active' => TRUE]);
        AclService::flush();
        return $role;
    }

    public static function updateRole(string $roleId, array $attributes): AclRole
    {
        $role = AclRole::findOrFail($roleId);
        $attributes = array_intersect_key($attributes, array_flip(['name', 'description', 'active']));
        if (array_key_exists('name', $attributes) && trim((string) $attributes['name']) === '') throw new ErrorMessage(translator('Enter the role name.'));

        $role->update($attributes);
        AclService::flush();
        return $role;
    }

    public static function deleteRole(string $roleId): void
    {
        AclRole::whereKey($roleId)->delete();
        AclService::flush();
    }

    public static function setRolePermission(string $roleId, string $permissionId, bool $granted): void
    {
        $role = AclRole::findOrFail($roleId);
        $granted
            ? $role->permissions()->syncWithoutDetaching([$permissionId])
            : $role->permissions()->detach($permissionId);
        AclService::flush();
    }

    /* ---------------------------------------------------------- Permissions */

    public static function createPermission(string $key, string $name, ?string $description = NULL, ?string $moduleId = NULL, ?string $routeId = NULL): AclPermission
    {
        $key = strtolower(trim($key));
        if (!preg_match(self::PERMISSION_KEY_PATTERN, $key)) throw new ErrorMessage(translator('The permission key may only hold lowercase letters, numbers, dots and dashes.'));
        if (trim($name) === '') throw new ErrorMessage(translator('Enter the permission name.'));
        if (AclPermission::where('key', $key)->exists()) throw new ErrorMessage(translator('A permission with this key already exists!'));
        $routeId = self::validateRoute($routeId, NULL);

        $permission = AclPermission::create([
            'key'         => $key,
            'name'        => trim($name),
            'description' => $description ?: NULL,
            'module_id'   => $moduleId ?: self::moduleOfRoute($routeId),
            'route_id'    => $routeId,
        ]);
        AclService::flush();
        return $permission;
    }

    public static function updatePermission(string $permissionId, array $attributes): AclPermission
    {
        $permission = AclPermission::findOrFail($permissionId);
        $attributes = array_intersect_key($attributes, array_flip(['name', 'description', 'module_id', 'route_id']));
        if (array_key_exists('name', $attributes) && trim((string) $attributes['name']) === '') throw new ErrorMessage(translator('Enter the permission name.'));
        foreach (['description', 'module_id', 'route_id'] as $nullable) {
            if (array_key_exists($nullable, $attributes) && $attributes[$nullable] === '') $attributes[$nullable] = NULL;
        }
        if (array_key_exists('route_id', $attributes)) {
            $attributes['route_id'] = self::validateRoute($attributes['route_id'], $permission->id);
            if ($attributes['route_id'] && empty($permission->module_id) && !array_key_exists('module_id', $attributes)) {
                $attributes['module_id'] = self::moduleOfRoute($attributes['route_id']);
            }
        }

        $permission->update($attributes);
        AclService::flush();
        return $permission;
    }

    public static function deletePermission(string $permissionId): void
    {
        AclPermission::whereKey($permissionId)->delete();
        AclService::flush();
    }

    /* ---------------------------------------------------------------- Users */

    public static function setUserRole(int $userId, string $roleId, bool $assigned): void
    {
        if ($assigned) {
            DB::table('acl_user_role')->insertOrIgnore(['user_id' => $userId, 'role_id' => $roleId]);
        } else {
            DB::table('acl_user_role')->where('user_id', $userId)->where('role_id', $roleId)->delete();
        }
        AclService::flush();
    }

    /**
     * Set a per-user override. TRUE = always allow, FALSE = always deny, NULL = follow the roles.
     */
    public static function setUserPermission(int $userId, string $permissionId, ?bool $allow): void
    {
        if ($allow === NULL) {
            DB::table('acl_user_permission')->where('user_id', $userId)->where('permission_id', $permissionId)->delete();
        } else {
            DB::table('acl_user_permission')->updateOrInsert(
                ['user_id' => $userId, 'permission_id' => $permissionId],
                ['allow' => $allow, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        AclService::flush();
    }

    /* --------------------------------------------------------- Old ACL import */

    /**
     * Copy the old ACL into the new one. Safe to run more than once: existing roles and
     * permissions (matched by key) are reused, existing assignments are kept.
     *   role_groups        -> acl_roles            (key = name)
     *   resources          -> acl_permissions      (key = resources)
     *   role_resources     -> acl_role_permission
     *   user_roles         -> acl_user_role
     *   special_privileges -> acl_user_permission  (allow)
     *   app_module_routes.resource_id -> acl_permissions.route_id (when still free)
     *
     * @return array<string, int> Counts of what was created.
     */
    public static function importFromLegacy(): array
    {
        $stats = ['roles' => 0, 'permissions' => 0, 'role_permissions' => 0, 'user_roles' => 0, 'user_permissions' => 0, 'routes' => 0];

        DB::transaction(function () use (&$stats) {
            /**Old resource ID => new permission ID */
            $permissionMap = [];
            foreach (DB::table('resources')->get() as $resource) {
                $key = strtolower(trim((string) $resource->resources));
                if ($key === '') continue;
                $permission = AclPermission::firstOrCreate(['key' => $key], ['name' => $resource->desc_hr ?: $key]);
                if ($permission->wasRecentlyCreated) $stats['permissions']++;
                $permissionMap[$resource->id] = $permission->id;
            }

            /**Old role group ID => new role ID */
            $roleMap = [];
            foreach (DB::table('role_groups')->get() as $group) {
                $key = self::normalizeRoleKey((string) $group->name);
                if ($key === '') continue;
                $role = AclRole::firstOrCreate(['key' => $key], ['name' => ($group->name_hr ?? NULL) ?: $group->name, 'active' => TRUE]);
                if ($role->wasRecentlyCreated) $stats['roles']++;
                $roleMap[$group->id] = $role->id;
            }

            foreach (DB::table('role_resources')->get() as $row) {
                if (!isset($roleMap[$row->role_id], $permissionMap[$row->resources_id])) continue;
                $stats['role_permissions'] += DB::table('acl_role_permission')->insertOrIgnore(['role_id' => $roleMap[$row->role_id], 'permission_id' => $permissionMap[$row->resources_id]]);
            }

            foreach (DB::table('user_roles')->get() as $row) {
                if (!isset($roleMap[$row->role_id])) continue;
                $stats['user_roles'] += DB::table('acl_user_role')->insertOrIgnore(['user_id' => $row->user_id, 'role_id' => $roleMap[$row->role_id]]);
            }

            foreach (DB::table('special_privileges')->get() as $row) {
                if (!isset($permissionMap[$row->resources_id])) continue;
                $stats['user_permissions'] += DB::table('acl_user_permission')->insertOrIgnore([
                    'user_id' => $row->user_id, 'permission_id' => $permissionMap[$row->resources_id], 'allow' => TRUE, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            foreach (AppModuleRoute::whereNotNull('resource_id')->where('resource_id', '!=', '')->get() as $route) {
                $permissionId = $permissionMap[$route->resource_id] ?? NULL;
                if ($permissionId === NULL || AclPermission::where('route_id', $route->id)->exists()) continue;
                $stats['routes'] += AclPermission::whereKey($permissionId)->whereNull('route_id')->update(['route_id' => $route->id, 'module_id' => $route->module_id]);
            }
        });

        AclService::flush();
        return $stats;
    }

    /* -------------------------------------------------------------- Lookups */

    /**
     * All permissions grouped by app module, for the checkbox lists.
     * [module label => [permission ID => ['key' => ..., 'name' => ...]]], modules A-Z, "General" last.
     *
     * @param string|null $search Filter on key or name.
     * @return array
     */
    public static function permissionCatalog(?string $search = NULL): array
    {
        $modules = DB::table('app_modules')->pluck('name', 'id')->all();
        $permissions = AclPermission::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('key', 'LIKE', '%' . $search . '%')->orWhere('name', 'LIKE', '%' . $search . '%')))
            ->orderBy('key')
            ->get(['id', 'key', 'name', 'module_id']);

        $general = translator('General');
        $catalog = [];
        foreach ($permissions as $permission) {
            $group = $modules[$permission->module_id] ?? $general;
            $catalog[$group][$permission->id] = ['key' => $permission->key, 'name' => $permission->name];
        }

        uksort($catalog, fn ($a, $b) => $a === $general ? 1 : ($b === $general ? -1 : strcasecmp($a, $b)));
        return $catalog;
    }

    /* -------------------------------------------------------------- Helpers */

    public static function normalizeRoleKey(string $key): string
    {
        $key = strtoupper(trim($key));
        $key = preg_replace('/[^A-Z0-9]+/', '_', $key);
        return trim($key, '_');
    }

    /**
     * A route can be bound to one permission only.
     */
    private static function validateRoute(?string $routeId, ?string $ownPermissionId): ?string
    {
        if ($routeId === NULL || $routeId === '') return NULL;
        if (!AppModuleRoute::whereKey($routeId)->exists()) throw new ErrorMessage(translator('The selected route does not exist!'));

        $taken = AclPermission::where('route_id', $routeId)
            ->when($ownPermissionId, fn ($q) => $q->where('id', '!=', $ownPermissionId))
            ->value('key');
        if ($taken) throw new ErrorMessage(translator('The route is already bound to the permission') . ': ' . $taken);

        return $routeId;
    }

    private static function moduleOfRoute(?string $routeId): ?string
    {
        return $routeId ? AppModuleRoute::whereKey($routeId)->value('module_id') : NULL;
    }
}
