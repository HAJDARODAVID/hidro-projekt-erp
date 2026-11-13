<?php

namespace App\Services\Application\Notifications;

use App\Models\Application\AppNotification;
use App\Models\User;
use App\Services\Application\CompanyContext;
use Illuminate\Support\Facades\Auth;

/**
 * Fluent builder, start it with Notify::type('...').
 *
 *   Notify::type('int_order')->title('New internal order')->message('...')->toUser($id)->url(route('...'))->send();
 *   Notify::type('general')->level('warning')->message('...')->permission('employees.worker-info.view')->send();
 */
class PendingNotification
{
    public string $type;
    public string $level;
    public string $title;
    public string $message = '';
    public array $data = [];
    public ?string $url = NULL;
    public ?int $recipientUserId = NULL;
    public ?string $permission;
    public ?int $companyId = NULL;
    public bool $companySet = FALSE;
    public ?int $causedByUserId;
    public ?string $fingerprint = NULL;

    /** DB connection to write with (NULL = default) */
    public ?string $connection = NULL;

    public function __construct(string $type)
    {
        $info = NotificationTypes::get($type);

        $this->type = $type;
        $this->level = $info['level'];
        $this->title = $info['label'];
        $this->permission = $info['permission'];
        $this->causedByUserId = Auth::id();
    }

    public function level(string $level): static
    {
        if (!in_array($level, NotificationTypes::LEVELS, TRUE)) {
            throw new \InvalidArgumentException("Unknown notification level [$level]");
        }
        $this->level = $level;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = mb_substr($title, 0, 255);
        return $this;
    }

    public function message(string $message): static
    {
        $this->message = mb_substr($message, 0, 60000);
        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function url(?string $url): static
    {
        $this->url = $url !== NULL ? mb_substr($url, 0, 2048) : NULL;
        return $this;
    }

    /**
     * Send to one user only (ignores the permission).
     */
    public function toUser(User|int $user): static
    {
        $this->recipientUserId = $user instanceof User ? $user->id : $user;
        return $this;
    }

    /**
     * Everybody holding the ACL permission (NULL = everybody, NotificationTypes::SUPER_ADMIN_ONLY = super admins).
     */
    public function permission(?string $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function company(?int $companyId): static
    {
        $this->companyId = $companyId;
        $this->companySet = TRUE;
        return $this;
    }

    public function causedBy(User|int|null $user): static
    {
        $this->causedByUserId = $user instanceof User ? $user->id : $user;
        return $this;
    }

    /**
     * Group with earlier notifications of the same type + company that have this key:
     * instead of a new row, the counter / last seen of the existing one goes up.
     */
    public function groupBy(string $key): static
    {
        $this->fingerprint = strlen($key) === 64 && ctype_xdigit($key) ? $key : hash('sha256', $key);
        return $this;
    }

    public function onConnection(?string $connection): static
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Company: explicit > recipient's > causer's > nobody's (NULL).
     */
    public function resolveCompanyId(): ?int
    {
        if ($this->companySet) return $this->companyId;

        $userId = $this->recipientUserId ?? $this->causedByUserId;
        if ($userId === NULL) return NULL;

        $user = Auth::id() === $userId ? Auth::user() : User::find($userId);
        return CompanyContext::idOf($user);
    }

    public function send(): AppNotification
    {
        return NotificationService::store($this);
    }
}
