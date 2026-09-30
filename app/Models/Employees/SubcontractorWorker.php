<?php

namespace App\Models\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A worker of a subcontractor company (legacy table: cooperator_workers).
 * Replaces the legacy App\Models\CooperatorWorkersModel.
 *
 * @property int $id
 * @property int $cooperator_id Subcontractor ID
 * @property string|null $firstName
 * @property string|null $lastName
 * @property int|null $status SubcontractorWorker::STATUS_*
 */
class SubcontractorWorker extends Model
{
    use HasFactory;

    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    const STATUSES = [
        self::STATUS_ACTIVE   => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    protected $table = 'cooperator_workers';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $fillable = [
        'cooperator_id',
        'firstName',
        'lastName',
        'status',
    ];

    protected $casts = [
        'cooperator_id' => 'integer',
        'status'        => 'integer',
    ];

    // Relationships

    /**
     * The subcontractor company the worker belongs to.
     *
     * @return BelongsTo
     */
    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class, 'cooperator_id', 'id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_ACTIVE);
    }

    /**
     * Workers of one subcontractor.
     */
    public function scopeOfSubcontractor(Builder $query, int $subcontractorID): Builder
    {
        return $query->where('cooperator_id', $subcontractorID);
    }

    /**
     * Filter by first name, last name, full name or ID, an empty search is ignored.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $like = '%' . $search . '%';
        return $query->where(function (Builder $q) use ($search, $like) {
            $q->where('firstName', 'LIKE', $like)
                ->orWhere('lastName', 'LIKE', $like)
                ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", [$like]);
            if (ctype_digit($search)) $q->orWhere('id', (int) $search);
        });
    }

    // Helpers

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Translatable status label (Active / Inactive).
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_INACTIVE];
    }
}
