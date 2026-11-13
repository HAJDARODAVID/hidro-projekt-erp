<?php

namespace App\Services\Employees;

use Illuminate\Support\Carbon;
use App\Models\Employees\Worker;
use App\Models\Employees\WorkerType;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerCertificate;

/**
 * Class CertificatesOverviewService.
 * What needs attention over ALL employed workers: medical check-ups, trainings and certificates that are
 * expired or due within N days, and what a worker's workplace requires but is missing.
 *
 * Same rules as the worker's tab (WorkerCertificatesService::markCurrent() / requirementsFor()), loaded in
 * two queries. Used by the CertificatesOverview component (global modal now, dashboard card later).
 */
class CertificatesOverviewService
{
    public const STATE_EXPIRED  = WorkerCertificate::STATUS_EXPIRED;
    public const STATE_DUE_SOON = WorkerCertificate::STATUS_DUE_SOON;
    public const STATE_MISSING  = WorkerCertificatesService::MISSING;

    /**Order of urgency */
    public const STATES = [self::STATE_EXPIRED, self::STATE_MISSING, self::STATE_DUE_SOON];

    /**
     * @param int $withinDays "due soon" = due within this many days
     * @return array [[
     *   'workerId', 'worker', 'workplaceId', 'workplace', 'type', 'title', 'state',
     *   'validUntil' => ?Carbon, 'daysLeft' => ?int, 'reason' => ?string, 'required' => bool
     * ]] sorted by urgency: expired (most overdue first), missing, due soon (soonest first)
     */
    public static function rows(int $withinDays = WorkerCertificate::DUE_SOON_DAYS, ?Carbon $today = NULL): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $limit = $today->copy()->addDays(max(0, $withinDays));

        $workers = Worker::with('workplace')
            ->whereIn('status', WorkerStatus::init()->areEmployed())
            ->whereIn('type', WorkerType::init()->getAllType(TRUE))
            ->get(['id', 'firstName', 'lastName', 'workplace_id']);
        $certificates = WorkerCertificate::whereIn('worker_id', $workers->pluck('id'))->get()->groupBy('worker_id');

        $rows = [];
        foreach ($workers as $worker) {
            $entries = WorkerCertificatesService::markCurrent($certificates->get($worker->id, collect()), $today);
            $base = [
                'workerId'    => $worker->id,
                'worker'      => trim($worker->firstName . ' ' . $worker->lastName),
                'workplaceId' => $worker->workplace?->id,
                'workplace'   => $worker->workplace?->key,
            ];

            /**Required by the workplace but missing */
            $requiredKeys = [];
            foreach ($worker->workplace ? WorkerCertificatesService::requirementsFor($worker->workplace, $entries) : [] as $requirement) {
                if ($requirement['entry']) $requiredKeys[$requirement['entry']->id] = TRUE;
                if ($requirement['state'] !== self::STATE_MISSING) continue;
                $rows[] = $base + [
                    'type' => $requirement['type'], 'title' => $requirement['title'], 'state' => self::STATE_MISSING,
                    'validUntil' => NULL, 'daysLeft' => NULL, 'reason' => $requirement['reason'], 'required' => TRUE,
                ];
            }

            /**Current entries that are expired or due within the days (required or not) */
            foreach ($entries as $entry) {
                if (!$entry->current || $entry->valid_until === NULL || $entry->valid_until->gt($limit)) continue;
                $rows[] = $base + [
                    'type'       => $entry->type,
                    'title'      => $entry->title,
                    'state'      => $entry->valid_until->lt($today) ? self::STATE_EXPIRED : self::STATE_DUE_SOON,
                    'validUntil' => $entry->valid_until,
                    'daysLeft'   => $entry->daysLeft($today),
                    'reason'     => NULL,
                    'required'   => isset($requiredKeys[$entry->id]),
                ];
            }
        }

        usort($rows, fn ($a, $b) => [array_search($a['state'], self::STATES, TRUE), $a['daysLeft'] ?? 0, $a['worker']]
            <=> [array_search($b['state'], self::STATES, TRUE), $b['daysLeft'] ?? 0, $b['worker']]);

        return $rows;
    }

    /**
     * Number of rows per state: [state => count]
     */
    public static function counts(array $rows): array
    {
        $counts = array_fill_keys(self::STATES, 0);
        foreach ($rows as $row) $counts[$row['state']]++;
        return $counts;
    }
}
