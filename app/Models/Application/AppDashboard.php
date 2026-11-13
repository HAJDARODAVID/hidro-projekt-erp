<?php

namespace App\Models\Application;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dashboard layout. Every user can have several layouts per dashboard type.
 * A private layout is visible to its owner only, a global one to everybody in the owner's company
 * (but only the owner can change it).
 *
 * company_id: there is no company logic yet, so it is NULL for everybody (NULL matches NULL = one shared "company").
 */
class AppDashboard extends Model
{
    public const TYPE_APP_SETTINGS = 'app-settings';
    public const TYPE_HOME = 'home';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_GLOBAL = 'global';

    protected $table = 'app_dashboard';

    protected $fillable = [
        'type',
        'user_id',
        'company_id',
        'name',
        'visibility',
        'layout',
    ];

    protected $casts = [
        'layout' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Layouts the user may see: their own + the global ones of their company.
     */
    public function scopeVisibleTo(Builder $query, int $userId, ?int $companyId): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('user_id', $userId)
            ->orWhere(fn ($g) => $g->global($companyId)));
    }

    /**
     * Global layouts of a company (NULL company = users without a company).
     */
    public function scopeGlobal(Builder $query, ?int $companyId): Builder
    {
        return $query->where('visibility', self::VISIBILITY_GLOBAL)
            ->when($companyId === NULL,
                fn ($q) => $q->whereNull('company_id'),
                fn ($q) => $q->where('company_id', $companyId));
    }

    /**
     * Company of a user. There is no company logic yet: users have no company_id, so this is NULL for now.
     * TODO: adjust once users belong to a company.
     */
    public static function companyIdOf(?User $user): ?int
    {
        $companyId = $user?->getAttribute('company_id');

        return $companyId === NULL ? NULL : (int) $companyId;
    }

    public function isGlobal(): bool
    {
        return $this->visibility === self::VISIBILITY_GLOBAL;
    }

    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== NULL && (int) $this->user_id === $userId;
    }
}
