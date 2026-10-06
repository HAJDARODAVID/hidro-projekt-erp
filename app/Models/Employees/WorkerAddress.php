<?php

namespace App\Models\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * The address of a worker (legacy table: worker_address), one record per worker.
 * Replaces the legacy App\Models\WorkerAddress.
 *
 * @property int $id
 * @property int $worker_id
 * @property string|null $street
 * @property string|null $town
 * @property int|null $zip
 * @property string|null $county
 */
class WorkerAddress extends Model
{
    use HasFactory;

    protected $table = 'worker_address';

    public $timestamps = FALSE;

    protected $fillable = [
        'worker_id',
        'street',
        'town',
        'zip',
        'county',
    ];

    protected $casts = [
        'worker_id' => 'integer',
        'zip'       => 'integer',
    ];

    // Relationships

    /**
     * The worker the address belongs to.
     *
     * @return BelongsTo
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }

    // Scopes

    /**
     * The address of one worker.
     */
    public function scopeOfWorker(Builder $query, int $workerID): Builder
    {
        return $query->where('worker_id', $workerID);
    }

    // Helpers

    /**
     * TRUE when none of the address values is filled.
     */
    public function isEmpty(): bool
    {
        return trim((string) $this->street) === ''
            && trim((string) $this->town) === ''
            && $this->zip === NULL
            && trim((string) $this->county) === '';
    }

    /**
     * The address in one line, e.g. "Frankopanska 12, 42250 Lepoglava, Varaždinska". Empty parts are left out.
     */
    public function getFullAddressAttribute(): string
    {
        $town = trim($this->zip . ' ' . $this->town);
        return implode(', ', array_filter([trim((string) $this->street), $town, trim((string) $this->county)], fn ($part) => $part !== ''));
    }
}
