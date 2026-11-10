<?php

namespace App\Services\Application\Acl;

use App\Models\User;
use App\Models\User\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;

/**
 * Class AclService.
 * Answers "may this user do X?" for the new ACL.
 *
 * Effective permissions of a user =
 *     (permissions of all their ACTIVE roles  +  their own allow overrides)
 *   -  their own deny overrides
 * A super admin may do everything.
 *
 * Results are cached per user. Every change in the ACL calls AclService::flush(),
 * which bumps a global version so all cached results go stale at once. No more
 * session copies, inv_update flags or view:clear: a change counts on the next request.
 */
class AclService
{
    private const VERSION_KEY = 'acl.version';

    /** @var User|null */
    private $user;

    private function __construct(?User $user)
    {
        $this->user = $user;
    }

    /**
     * Instance for a given user (model or ID).
     *
     * @param User|int|null $user
     * @return self
     */
    public static function forUser($user): self
    {
        if (is_numeric($user)) $user = User::find((int) $user);
        if ($user !== NULL && !$user instanceof User) $user = User::find($user->getAuthIdentifier());
        return new self($user);
    }

    /**
     * Instance for the logged in user.
     *
     * @return self
     */
    public static function current(): self
    {
        return self::forUser(Auth::id());
    }

    /**
     * Invalidate every cached ACL result. Call after any change in roles, permissions or assignments.
     *
     * @return void
     */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    private static function cacheKey(string $suffix): string
    {
        return 'acl.v' . self::version() . '.' . $suffix;
    }

    /**
     * Super admins bypass the ACL. Both the old numeric type and the new user_type are honoured.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if ($this->user === NULL) return FALSE;
        return $this->user->type == User::USER_TYPE_SUPER_ADMIN
            || $this->user->user_type == UserType::USER_TYPE_SUPER_ADMIN;
    }

    /**
     * All permission keys the user effectively holds.
     *
     * @return array<string>
     */
    public function permissions(): array
    {
        if ($this->user === NULL) return [];

        return Cache::rememberForever(self::cacheKey('user.' . $this->user->id), function () {
            $fromRoles = DB::table('acl_user_role')
                ->join('acl_roles', 'acl_roles.id', '=', 'acl_user_role.role_id')
                ->join('acl_role_permission', 'acl_role_permission.role_id', '=', 'acl_roles.id')
                ->join('acl_permissions', 'acl_permissions.id', '=', 'acl_role_permission.permission_id')
                ->where('acl_user_role.user_id', $this->user->id)
                ->where('acl_roles.active', TRUE)
                ->pluck('acl_permissions.key')
                ->all();

            $overrides = DB::table('acl_user_permission')
                ->join('acl_permissions', 'acl_permissions.id', '=', 'acl_user_permission.permission_id')
                ->where('acl_user_permission.user_id', $this->user->id)
                ->pluck('acl_user_permission.allow', 'acl_permissions.key')
                ->all();

            $allowed = array_merge($fromRoles, array_keys(array_filter($overrides)));
            $denied = array_keys(array_filter($overrides, fn ($allow) => !$allow));

            return array_values(array_diff(array_unique($allowed), $denied));
        });
    }

    /**
     * Does the user hold the permission?
     *
     * @param string $permissionKey
     * @return bool
     */
    public function can(string $permissionKey): bool
    {
        if ($this->isSuperAdmin()) return TRUE;
        return in_array($permissionKey, $this->permissions(), TRUE);
    }

    /**
     * Does the user hold at least one of the permissions?
     *
     * @param string ...$permissionKeys
     * @return bool
     */
    public function canAny(string ...$permissionKeys): bool
    {
        foreach ($permissionKeys as $key) {
            if ($this->can($key)) return TRUE;
        }
        return FALSE;
    }

    /**
     * May the user open the route? A route without a bound permission is open to everyone logged in.
     *
     * @param string|null $routeName
     * @return bool
     */
    public function canRoute(?string $routeName): bool
    {
        if ($routeName === NULL) return TRUE;
        $permissionKey = self::routePermissions()[$routeName] ?? NULL;
        return $permissionKey === NULL || $this->can($permissionKey);
    }

    /**
     * Is the key a permission known to the ACL? Used by the Gate hook so unknown abilities fall through.
     *
     * @param string $permissionKey
     * @return bool
     */
    public static function isKnownPermission(string $permissionKey): bool
    {
        try {
            $known = Cache::rememberForever(self::cacheKey('keys'), fn () => DB::table('acl_permissions')->pluck('key')->all());
        } catch (QueryException $e) {
            return FALSE; // ACL tables not migrated yet
        }
        return in_array($permissionKey, $known, TRUE);
    }

    /**
     * Map of route name => permission key, for all routes that are bound to a permission.
     *
     * @return array<string, string>
     */
    public static function routePermissions(): array
    {
        try {
            return Cache::rememberForever(self::cacheKey('routes'), function () {
                return DB::table('acl_permissions')
                    ->join('app_module_routes', 'app_module_routes.id', '=', 'acl_permissions.route_id')
                    ->whereNotNull('app_module_routes.route_name')
                    ->pluck('acl_permissions.key', 'app_module_routes.route_name')
                    ->all();
            });
        } catch (QueryException $e) {
            return []; // ACL tables not migrated yet
        }
    }
}
