<?php

namespace App\Models\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A subcontractor company (legacy table: cooperators).
 * Replaces the legacy App\Models\CooperatorsModel.
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $status Subcontractor::STATUS_*
 */
class Subcontractor extends Model
{
    use HasFactory;

    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_DELETED  = -1;

    const STATUSES = [
        self::STATUS_ACTIVE   => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
        self::STATUS_DELETED  => 'Deleted',
    ];

    /**Statuses a user can pick when creating/editing (deleted is set only by deleting) */
    const SELECTABLE_STATUSES = [
        self::STATUS_ACTIVE   => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    protected $table = 'cooperators';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Relationships

    /**
     * All workers of the subcontractor.
     *
     * @return HasMany
     */
    public function workers(): HasMany
    {
        return $this->hasMany(SubcontractorWorker::class, 'cooperator_id', 'id');
    }

    /**
     * The active workers of the subcontractor.
     *
     * @return HasMany
     */
    public function activeWorkers(): HasMany
    {
        return $this->workers()->where('status', SubcontractorWorker::STATUS_ACTIVE);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeDeleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DELETED);
    }

    /**
     * Active and inactive subcontractors (everything except the deleted ones).
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_DELETED);
    }

    /**
     * Filter by name, an empty search is ignored.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        return $search === '' ? $query : $query->where('name', 'LIKE', '%' . $search . '%');
    }

    // Helpers

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Translatable status label (Active / Inactive / Deleted).
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_INACTIVE];
    }
}
