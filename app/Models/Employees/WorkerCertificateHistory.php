<?php

namespace App\Models\Employees;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The old version of a worker's certificate / check: written when the row is renewed or deleted
 * (WorkerCertificatesService::renew() / delete()). Read only.
 *
 * @property int|null $certificate_id
 * @property int $worker_id
 * @property string $type
 * @property string $title
 * @property Carbon|null $issued_at
 * @property Carbon|null $valid_until
 * @property string|null $note
 * @property string $action renewed | deleted
 * @property Carbon|null $archived_at
 */
class WorkerCertificateHistory extends Model
{
    protected $table = 'worker_certificate_history';

    public const ACTION_RENEWED = 'renewed';
    public const ACTION_DELETED = 'deleted';

    protected $fillable = [
        'certificate_id', 'worker_id', 'type', 'title', 'issued_at', 'valid_until', 'note',
        'action', 'created_by', 'archived_by', 'archived_at',
    ];

    protected $casts = [
        'certificate_id' => 'integer',
        'worker_id'      => 'integer',
        'issued_at'      => 'date',
        'valid_until'    => 'date',
        'archived_at'    => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    /**
     * Copy a row into the history.
     */
    public static function archive(WorkerCertificate $row, string $action, ?int $userId = NULL): self
    {
        return self::create([
            'certificate_id' => $row->id,
            'worker_id'      => $row->worker_id,
            'type'           => $row->type,
            'title'          => $row->title,
            'issued_at'      => $row->issued_at,
            'valid_until'    => $row->valid_until,
            'note'           => $row->note,
            'action'         => $action,
            'created_by'     => $row->created_by,
            'archived_by'    => $userId,
            'archived_at'    => now(),
        ]);
    }
}
