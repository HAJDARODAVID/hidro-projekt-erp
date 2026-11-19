<?php

namespace App\Services\Admin;

use App\Exceptions\ErrorMessage;
use App\Models\CooperatorWorkersModel;
use App\Models\Employees\Worker;
use App\Models\User;
use App\Models\User\UserType;
use App\Services\Application\Acl\AclService;
use App\Services\Application\AppTranslationsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class ManageUsersService.
 * All writes of Admin - Users go through here. Validation problems are thrown as ErrorMessage, the caller shows them.
 *
 * Rules:
 *  - only a super admin may create, change or (de)activate a super admin, or make a user one
 *  - nobody may deactivate themselves, and the last active super admin stays
 *  - a worker or a subcontractor worker can be linked to one user only
 *  - the legacy numeric `type` (used by the app) and the new `user_type` are kept in sync
 */
class ManageUsersService
{
    public const MIN_PASSWORD_LENGTH = 8;

    /** Legacy users.type => users.user_type */
    public const TYPE_MAP = [
        User::USER_TYPE_MANAGER      => UserType::USER_TYPE_MANAGER,
        User::USER_TYPE_ADMIN_STAFF  => UserType::USER_TYPE_ADMIN_STAFF,
        User::USER_TYPE_GROUP_LEADER => UserType::USER_TYPE_GROUP_LEADER,
        User::USER_TYPE_SUPER_ADMIN  => UserType::USER_TYPE_SUPER_ADMIN,
        User::USER_TYPE_COOPERATOR   => UserType::USER_TYPE_COOPERATOR,
    ];

    /** @var User|null the user making the change */
    private $actor;

    private function __construct(?User $actor)
    {
        $this->actor = $actor;
    }

    public static function as(?User $actor): self
    {
        return new self($actor);
    }

    public static function asCurrentUser(): self
    {
        return new self(auth()->id() ? User::find(auth()->id()) : NULL);
    }

    /* ------------------------------------------------------------- Options */

    /**
     * User types the actor may give [type => label]. Only a super admin may give the super admin type.
     */
    public function assignableTypes(): array
    {
        $types = [];
        foreach (User::USER_TYPE as $type => $label) {
            if ($type === User::USER_TYPE_SUPER_ADMIN && !$this->actorIsSuperAdmin()) continue;
            $types[$type] = translator($label);
        }
        return $types;
    }

    /** All user types [type => label], for filters and labels. */
    public static function typeLabels(): array
    {
        return array_map(fn ($label) => translator($label), User::USER_TYPE);
    }

    /* ---------------------------------------------------------------- Writes */

    /**
     * Create a user. Without a password a random one is generated.
     *
     * @return array{0: User, 1: string} the user and the plain password (show it once)
     */
    public function create(array $data): array
    {
        $data = $this->validated($data, NULL);
        $password = (string) ($data['password'] ?? '');
        if ($password === '') $password = self::generatePassword();
        $this->validatePassword($password);

        $user = DB::transaction(function () use ($data, $password) {
            $user = new User();
            $user->fill($this->attributes($data));
            $user->password = $password; // hashed by the cast
            $user->active = TRUE;
            $user->save();
            return $user;
        });

        return [$user, $password];
    }

    /**
     * Change the details of a user (not the password, not the status).
     */
    public function update(User $user, array $data): User
    {
        $this->guardSuperAdminTarget($user);
        $data = $this->validated($data, $user);

        $attributes = $this->attributes($data);
        if ((int) $user->type === User::USER_TYPE_SUPER_ADMIN && (int) $attributes['type'] !== User::USER_TYPE_SUPER_ADMIN) {
            $this->guardLastSuperAdmin($user);
        }

        // the language and the type are read into the session on the home page: refresh it there
        $refreshSession = $user->lang !== $attributes['lang'] || (int) $user->type !== (int) $attributes['type'];

        $user->fill($attributes);
        if ($refreshSession) $user->inv_update = TRUE;
        $user->save();

        if ($refreshSession) AclService::flush(); // super admin status may have changed
        return $user;
    }

    /**
     * Activate or deactivate a user. A deactivated user can not sign in and is signed out on the next request.
     */
    public function setActive(User $user, bool $active): User
    {
        $this->guardSuperAdminTarget($user);
        if (!$active) {
            if ($this->actor && $this->actor->id === $user->id) throw new ErrorMessage(translator('You can not deactivate yourself!'));
            if ((int) $user->type === User::USER_TYPE_SUPER_ADMIN) $this->guardLastSuperAdmin($user);
        }

        $user->active = $active;
        // a new remember token: "remember me" cookies of the user stop working
        if (!$active) $user->setRememberToken(Str::random(60));
        $user->save();
        return $user;
    }

    /**
     * Set a new password. Without a password a random one is generated.
     *
     * @return string the plain password (show it once)
     */
    public function resetPassword(User $user, ?string $password = NULL): string
    {
        $this->guardSuperAdminTarget($user);
        $password = (string) $password;
        if ($password === '') $password = self::generatePassword();
        $this->validatePassword($password);

        $user->password = $password;
        $user->setRememberToken(Str::random(60));
        $user->save();
        return $password;
    }

    /**
     * Random password that is easy to read out (no 0/O, 1/l/I).
     */
    public static function generatePassword(int $length = 10): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) $password .= $chars[random_int(0, strlen($chars) - 1)];
        return $password;
    }

    /* ---------------------------------------------------------- Validation */

    private function validated(array $data, ?User $user): array
    {
        $data = [
            'name'      => trim((string) ($data['name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email'     => strtolower(trim((string) ($data['email'] ?? ''))),
            'type'      => (int) ($data['type'] ?? 0),
            'lang'      => trim((string) ($data['lang'] ?? '')),
            'link'      => (string) ($data['link'] ?? ''),
            'password'  => $data['password'] ?? NULL,
        ];

        if ($data['name'] === '') throw new ErrorMessage(translator('Enter the first name.'));
        if (mb_strlen($data['name']) > 255 || mb_strlen($data['last_name']) > 255) throw new ErrorMessage(translator('The name is too long.'));
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 255) throw new ErrorMessage(translator('Enter a valid email address.'));
        $emailTaken = User::where('email', $data['email'])->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists();
        if ($emailTaken) throw new ErrorMessage(translator('A user with this email already exists!'));

        if (!array_key_exists($data['type'], User::USER_TYPE)) throw new ErrorMessage(translator('Select the user type.'));
        if ($data['type'] === User::USER_TYPE_SUPER_ADMIN && !$this->actorIsSuperAdmin()) {
            throw new ErrorMessage(translator('Only a super admin can make a user super admin!'));
        }

        if ($data['lang'] === '') $data['lang'] = AppTranslationsService::defaultLanguage();
        if (!AppTranslationsService::isAllowedLanguage($data['lang'])) throw new ErrorMessage(translator('The language is not allowed'));

        [$data['worker_id'], $data['coop_id']] = $this->validatedLink($data['link'], $user);
        return $data;
    }

    /**
     * "" (none), "worker:ID" or "coop:ID" => [worker_id, coop_id]
     */
    private function validatedLink(string $link, ?User $user): array
    {
        if ($link === '') return [NULL, NULL];
        if (!preg_match('/^(worker|coop):(\d+)$/', $link, $match)) throw new ErrorMessage(translator('Select the linked worker again.'));

        $id = (int) $match[2];
        $column = $match[1] === 'worker' ? 'worker_id' : 'coop_id';
        $exists = $match[1] === 'worker' ? Worker::whereKey($id)->exists() : CooperatorWorkersModel::whereKey($id)->exists();
        if (!$exists) throw new ErrorMessage(translator('The linked worker does not exist!'));

        $taken = User::where($column, $id)->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists();
        if ($taken) throw new ErrorMessage(translator('This worker is already linked to another user!'));

        return $column === 'worker_id' ? [$id, NULL] : [NULL, $id];
    }

    private function validatePassword(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ErrorMessage(translator('The password must have at least') . ' ' . self::MIN_PASSWORD_LENGTH . ' ' . translator('characters.'));
        }
        if (mb_strlen($password) > 72) throw new ErrorMessage(translator('The password is too long.'));
    }

    private function attributes(array $data): array
    {
        return [
            'name'      => $data['name'],
            'last_name' => $data['last_name'] !== '' ? $data['last_name'] : NULL,
            'email'     => $data['email'],
            'type'      => $data['type'],
            'user_type' => self::TYPE_MAP[$data['type']],
            'lang'      => $data['lang'],
            'worker_id' => $data['worker_id'],
            'coop_id'   => $data['coop_id'],
        ];
    }

    /* -------------------------------------------------------------- Guards */

    private function actorIsSuperAdmin(): bool
    {
        return $this->actor !== NULL && AclService::forUser($this->actor)->isSuperAdmin();
    }

    public static function isSuperAdmin(User $user): bool
    {
        return (int) $user->type === User::USER_TYPE_SUPER_ADMIN || $user->user_type === UserType::USER_TYPE_SUPER_ADMIN;
    }

    /** May the actor change this user at all? */
    public function canManage(User $user): bool
    {
        return !self::isSuperAdmin($user) || $this->actorIsSuperAdmin();
    }

    private function guardSuperAdminTarget(User $user): void
    {
        if (!$this->canManage($user)) throw new ErrorMessage(translator('Only a super admin can change a super admin!'));
    }

    private function guardLastSuperAdmin(User $user): void
    {
        $others = User::whereKeyNot($user->id)
            ->where('active', TRUE)
            ->where(fn ($q) => $q->where('type', User::USER_TYPE_SUPER_ADMIN)->orWhere('user_type', UserType::USER_TYPE_SUPER_ADMIN))
            ->exists();
        if (!$others) throw new ErrorMessage(translator('This is the last active super admin, it has to stay!'));
    }
}
