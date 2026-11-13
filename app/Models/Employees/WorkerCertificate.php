<?php

namespace App\Models\Employees;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A medical check-up, training or certificate of a worker, with its due date (valid_until).
 * A renewal is a new row; the newest row of a type + title is the current one.
 *
 * @property int $id
 * @property int $worker_id
 * @property string $type
 * @property string $title
 * @property Carbon|null $issued_at
 * @property Carbon|null $valid_until
 * @property string|null $note
 */
class WorkerCertificate extends Model
{
    protected $table = 'worker_certificates';

    /**
     * Types: key => [label, one of a kind?]. One of a kind: the title is the label (only one medical check-up
     * is current), the others need a title (which licence, which certificate).
     */
    public const TYPES = [
        'medical'   => ['Medical check-up', TRUE],
        'safety'    => ['Safety at work training (ZNR)', TRUE],
        'fire'      => ['Fire protection training', TRUE],
        'first-aid' => ['First aid', TRUE],
        'licence'   => ['Licence / certificate', FALSE],
        'other'     => ['Other', FALSE],
    ];

    /**Due within this many days = "due soon" */
    public const DUE_SOON_DAYS = 30;

    public const STATUS_VALID     = 'valid';
    public const STATUS_DUE_SOON  = 'due-soon';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_NO_EXPIRY = 'no-expiry';

    protected $fillable = ['worker_id', 'type', 'title', 'issued_at', 'valid_until', 'note', 'created_by'];

    protected $casts = [
        'worker_id'   => 'integer',
        'issued_at'   => 'date',
        'valid_until' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type][0] ?? $type;
    }

    public static function isOneOfAKind(string $type): bool
    {
        return (bool) (self::TYPES[$type][1] ?? FALSE);
    }

    /**
     * Status by the due date (today counts as still valid).
     */
    public function status(?Carbon $today = NULL): string
    {
        $today = ($today ?? now())->copy()->startOfDay();
        if ($this->valid_until === NULL) return self::STATUS_NO_EXPIRY;
        if ($this->valid_until->lt($today)) return self::STATUS_EXPIRED;
        if ($this->valid_until->lte($today->copy()->addDays(self::DUE_SOON_DAYS))) return self::STATUS_DUE_SOON;
        return self::STATUS_VALID;
    }

    /**Days until the due date (negative = overdue), NULL without one */
    public function daysLeft(?Carbon $today = NULL): ?int
    {
        if ($this->valid_until === NULL) return NULL;
        return (int) ($today ?? now())->copy()->startOfDay()->diffInDays($this->valid_until, FALSE);
    }
}
