<?php

namespace App\Models\Application;

use App\Models\User;
use App\Services\Application\Acl\AclService;
use App\Services\Application\CompanyContext;
use App\Services\Application\Notifications\NotificationTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A notification (system error, info for a user, ...). Create them with Notify::type(...)->send().
 *
 * Visibility for a user (scopeVisibleTo):
 *   same company AND ( sent directly to the user
 *                      OR not sent to anybody directly AND (no permission OR the user holds the permission) )
 * Super admins hold every permission, the SUPER_ADMIN_ONLY pseudo permission is held by nobody else.
 */
class AppNotification extends Model
{
    protected $table = 'app_notification';

    protected $fillable = [
        'type',
        'level',
        'title',
        'message',
        'data',
        'fingerprint',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'user_id',
        'recipient_user_id',
        'permission',
        'company_id',
        'url',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'data'          => 'array',
        'occurrences'   => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'resolved_at'   => 'datetime',
    ];

    /* ---------------------------------------------------------------- relations */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AppNotificationRead::class, 'notification_id');
    }

    /* ---------------------------------------------------------------- scopes */

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $acl = AclService::forUser($user);
        $isSuperAdmin = $acl->isSuperAdmin();
        $permissions = $isSuperAdmin ? [] : $acl->permissions();

        CompanyContext::scope($query, CompanyContext::idOf($user), $this->qualifyColumn('company_id'));

        return $query->where(fn ($q) => $q
            ->where('recipient_user_id', $user->id)
            ->orWhere(fn ($broadcast) => $broadcast
                ->whereNull('recipient_user_id')
                ->when(!$isSuperAdmin, fn ($p) => $p->where(fn ($perm) => $perm
                    ->whereNull('permission')
                    ->orWhereIn('permission', $permissions)))));
    }

    public function scopeOfType(Builder $query, string ...$types): Builder
    {
        return $query->whereIn('type', $types);
    }

    public function scopeMinLevel(Builder $query, string $minLevel): Builder
    {
        return $query->whereIn('level', NotificationTypes::levelsFrom($minLevel));
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeUnreadBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $userId));
    }

    public function scopeSeenSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('last_seen_at', '>=', $since);
    }

    /* ---------------------------------------------------------------- helpers */

    public function isResolved(): bool
    {
        return $this->resolved_at !== NULL;
    }

    public function isReadBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->relationLoaded('reads')
            ? $this->reads->contains('user_id', $userId)
            : $this->reads()->where('user_id', $userId)->exists();
    }

    public function typeInfo(): array
    {
        return NotificationTypes::exists($this->type) ? NotificationTypes::get($this->type) : ['label' => $this->type, 'icon' => 'bell'];
    }

    public function levelStyle(): array
    {
        return NotificationTypes::LEVEL_STYLE[$this->level] ?? NotificationTypes::LEVEL_STYLE['info'];
    }
}
