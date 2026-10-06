<?php

namespace App\Models\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * The contact info of a worker (legacy table: worker_contact), one record per worker.
 * Replaces the legacy App\Models\WorkerContact.
 *
 * @property int $id
 * @property int $worker_id
 * @property string|null $mob Mobile phone number
 * @property string|null $email
 */
class WorkerContact extends Model
{
    use HasFactory;

    protected $table = 'worker_contact';

    public $timestamps = FALSE;

    protected $fillable = [
        'worker_id',
        'mob',
        'email',
    ];

    protected $casts = [
        'worker_id' => 'integer',
    ];

    // Relationships

    /**
     * The worker the contact info belongs to.
     *
     * @return BelongsTo
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }

    // Scopes

    /**
     * The contact info of one worker.
     */
    public function scopeOfWorker(Builder $query, int $workerID): Builder
    {
        return $query->where('worker_id', $workerID);
    }

    // Helpers

    public function hasMob(): bool
    {
        return trim((string) $this->mob) !== '';
    }

    public function hasEmail(): bool
    {
        return trim((string) $this->email) !== '';
    }

    /**
     * TRUE when neither the phone nor the email is filled.
     */
    public function isEmpty(): bool
    {
        return !$this->hasMob() && !$this->hasEmail();
    }
}
