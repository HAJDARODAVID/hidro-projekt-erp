<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\Employees\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Employees\WorkerCertificateHistory;
use Illuminate\Support\Facades\Validator;
use App\Models\Employees\WorkerCertificate;
use App\Services\Config\WorkplaceFieldsConfigService;

/**
 * Class WorkerCertificatesService.
 * Medical check-ups, trainings and certificates of a worker (Employees -> Worker info -> Certificates & checks).
 *
 * worker_certificates holds the CURRENT row of each worker + type + title (one each). Renewing or deleting
 * a row copies its old version to worker_certificate_history (WorkerCertificateHistory).
 *
 *  - entries():      the current rows of the worker, with their status
 *  - requirements(): what the worker's workplace requires (medical check-up interval, ZNR training,
 *                    licences) and the current row of each, or "missing"
 *  - save():         new row / correction of a row (no history); a second row of the same is refused
 *  - renew():        new dates + note, the old version goes to the history
 *  - delete():       the row goes to the history
 *  - history():      the old versions
 */
class WorkerCertificatesService extends BaseService
{
    public const MISSING = 'missing';

    public static function init(): self
    {
        return new self();
    }

    /**
     * The rows of the worker, by due date (overdue on top).
     * (markCurrent() still guards against two rows of the same from before the history table: the older one is
     * then not "current".)
     *
     * @return Collection<WorkerCertificate> with ->current (bool) and ->state (status) set
     */
    public static function entries(int $workerId): Collection
    {
        return self::markCurrent(WorkerCertificate::where('worker_id', $workerId)->get());
    }

    /**
     * Mark the newest row of each type + title as current (->current) and set its status (->state).
     * Used for one worker (entries()) and for all of them at once (CertificatesOverviewService).
     *
     * @param Collection<WorkerCertificate> $rows rows of ONE worker
     * @return Collection current first (by due date, overdue on top), then the replaced ones
     */
    public static function markCurrent(Collection $rows, ?Carbon $today = NULL): Collection
    {
        $rows = $rows->sortByDesc(fn ($row) => [
            $row->issued_at?->format('Ymd') ?? '00000000',
            $row->valid_until?->format('Ymd') ?? '00000000',
            $row->id,
        ])->values();

        $seen = [];
        foreach ($rows as $row) {
            $group = $row->type . '|' . mb_strtolower($row->title);
            $row->current = !isset($seen[$group]);
            $seen[$group] = TRUE;
            $row->state = $row->status($today);
        }

        return $rows->sortBy(fn ($row) => [
            $row->current ? 0 : 1,
            $row->valid_until ? $row->valid_until->format('Ymd') : '99999999',
        ])->values();
    }

    /**
     * What the worker's workplace requires, with the current row of each.
     *
     * @return array [['type', 'title', 'reason', 'entry' => ?WorkerCertificate, 'state' => status|MISSING]]
     */
    public static function requirements(int $workerId, ?Collection $entries = NULL): array
    {
        $workplace = Worker::with('workplace')->find($workerId)?->workplace;
        if ($workplace === NULL) return [];

        return self::requirementsFor($workplace, $entries ?? self::entries($workerId));
    }

    /**
     * Requirements of a workplace checked against already loaded rows (markCurrent()) of one worker.
     *
     * @param \App\Models\Employees\Workplace $workplace
     * @param Collection<WorkerCertificate> $entries
     * @return array see requirements()
     */
    public static function requirementsFor($workplace, Collection $entries): array
    {
        $wanted = [];
        if ((int) $workplace->medical_check_months > 0) {
            $wanted[] = ['medical', WorkerCertificate::typeLabel('medical'), translator('every') . ' ' . $workplace->medical_check_months . ' ' . translator('months')];
        }
        if ($workplace->safety_training) {
            $wanted[] = ['safety', WorkerCertificate::typeLabel('safety'), translator('required by the workplace')];
        }
        foreach ((array) $workplace->licences as $licence) {
            $wanted[] = ['licence', (string) $licence, translator('licence of the workplace')];
        }

        $output = [];
        foreach ($wanted as [$type, $title, $reason]) {
            $entry = $entries->first(fn ($row) => $row->current && $row->type === $type
                && (WorkerCertificate::isOneOfAKind($type) || mb_strtolower($row->title) === mb_strtolower($title)));
            $output[] = ['type' => $type, 'title' => $title, 'reason' => $reason, 'entry' => $entry, 'state' => $entry?->state ?? self::MISSING];
        }
        return $output;
    }

    /**
     * Suggested due date: for a medical check-up the interval of the workplace after the check-up date.
     */
    public static function suggestValidUntil(int $workerId, string $type, ?string $issuedAt): ?string
    {
        if ($type !== 'medical' || !$issuedAt) return NULL;
        $months = (int) Worker::with('workplace')->find($workerId)?->workplace?->medical_check_months;
        if ($months <= 0) return NULL;
        try {
            return Carbon::parse($issuedAt)->addMonthsNoOverflow($months)->format('Y-m-d');
        } catch (\Throwable $th) {
            return NULL;
        }
    }

    /**
     * Titles to suggest for licences / certificates: the licences of the workplaces catalog (company options).
     */
    public static function licenceSuggestions(): array
    {
        return (array) (app(WorkplaceFieldsConfigService::class)->visibleFields()['licences']['options'] ?? []);
    }

    /**
     * Create or change a row.
     *
     * @param array $data ['type', 'title', 'issued_at', 'valid_until', 'note']
     * @param int|null $id NULL = new
     * @return self data: the WorkerCertificate
     */
    public function save(int $workerId, array $data, ?int $id = NULL): self
    {
        if (!Worker::whereKey($workerId)->exists()) return $this->setErrorMessage(translator('Worker not found!'));

        $data = array_map(fn ($value) => is_string($value) ? (trim($value) === '' ? NULL : trim($value)) : $value, $data);
        $type = (string) ($data['type'] ?? '');
        /**One-of-a-kind types (medical, ZNR, ...) are named by the type */
        if (isset(WorkerCertificate::TYPES[$type]) && WorkerCertificate::isOneOfAKind($type)) $data['title'] = WorkerCertificate::typeLabel($type);

        $validator = Validator::make($data, [
            'type'        => ['required', 'in:' . implode(',', array_keys(WorkerCertificate::TYPES))],
            'title'       => ['required', 'string', 'max:255'],
            'issued_at'   => ['nullable', 'date'],
            'valid_until' => array_filter(['nullable', 'date', !empty($data['issued_at']) ? 'after_or_equal:issued_at' : NULL]),
            'note'        => ['nullable', 'string', 'max:255'],
        ], [], [
            'type' => translator('Type'), 'title' => translator('Name'), 'issued_at' => translator('Done / issued on'),
            'valid_until' => translator('Valid until'), 'note' => translator('Note'),
        ]);
        if ($validator->fails()) return $this->setErrorMessage($validator->errors()->first());

        $values = [
            'type'        => $type,
            'title'       => $data['title'],
            'issued_at'   => $data['issued_at'] ?? NULL,
            'valid_until' => $data['valid_until'] ?? NULL,
            'note'        => $data['note'] ?? NULL,
        ];

        /**One current row per worker + type + title: a new one of the same is a renewal */
        $duplicate = WorkerCertificate::where('worker_id', $workerId)->where('type', $type)->where('title', $values['title'])
            ->when($id !== NULL, fn ($query) => $query->whereKeyNot($id))
            ->exists();
        if ($duplicate) {
            return $this->setErrorMessage(translator('The worker already has this entry. Use "Renew" on it, the old one goes to the history.'));
        }

        try {
            if ($id === NULL) {
                $row = WorkerCertificate::create($values + ['worker_id' => $workerId, 'created_by' => Auth::id()]);
            } else {
                /**Only a row of this worker */
                $row = WorkerCertificate::where('worker_id', $workerId)->find($id);
                if ($row === NULL) return $this->setErrorMessage(translator('The entry does not exist.'));
                $row->update($values);
            }
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }

        return $this->setSuccessMessage(translator('Saved!'), $row);
    }

    /**
     * Renew a row: the current version goes to the history (action "renewed"), the row gets the new dates and note.
     *
     * @param array $data ['issued_at', 'valid_until' (required), 'note']
     * @return self data: the renewed WorkerCertificate
     */
    public function renew(int $workerId, int $id, array $data): self
    {
        $row = WorkerCertificate::where('worker_id', $workerId)->find($id);
        if ($row === NULL) return $this->setErrorMessage(translator('The entry does not exist.'));

        $data = array_map(fn ($value) => is_string($value) ? (trim($value) === '' ? NULL : trim($value)) : $value, $data);
        $validator = Validator::make($data, [
            'issued_at'   => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issued_at'],
            'note'        => ['nullable', 'string', 'max:255'],
        ], [], [
            'issued_at' => translator('Start date'), 'valid_until' => translator('Due date'), 'note' => translator('Note'),
        ]);
        if ($validator->fails()) return $this->setErrorMessage($validator->errors()->first());

        try {
            DB::transaction(function () use ($row, $data) {
                WorkerCertificateHistory::archive($row, WorkerCertificateHistory::ACTION_RENEWED, Auth::id());
                $row->update([
                    'issued_at'   => $data['issued_at'],
                    'valid_until' => $data['valid_until'],
                    'note'        => $data['note'] ?? NULL,
                ]);
            });
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }

        return $this->setSuccessMessage(translator('Renewed!'), $row->fresh());
    }

    /**
     * Suggested dates of a renewal: start = the current due date (today without one); due date = the workplace
     * interval for a medical check-up, otherwise as long as the current row was valid.
     *
     * @return array ['issued_at' => Y-m-d, 'valid_until' => ?Y-m-d]
     */
    public static function suggestRenewal(WorkerCertificate $row, ?string $start = NULL): array
    {
        try {
            $start = $start ? Carbon::parse($start) : ($row->valid_until?->copy() ?? now()->startOfDay());
        } catch (\Throwable $th) {
            return ['issued_at' => $start, 'valid_until' => NULL];
        }

        $until = NULL;
        $medical = self::suggestValidUntil($row->worker_id, $row->type, $start->format('Y-m-d'));
        if ($medical !== NULL) {
            $until = $medical;
        } elseif ($row->issued_at && $row->valid_until && $row->valid_until->gt($row->issued_at)) {
            /**Same length: whole months when it was whole months (1 year, 6 months, ...), else days */
            $months = (int) $row->issued_at->diffInMonths($row->valid_until);
            $until = $months > 0 && $row->issued_at->copy()->addMonthsNoOverflow($months)->isSameDay($row->valid_until)
                ? $start->copy()->addMonthsNoOverflow($months)
                : $start->copy()->addDays((int) $row->issued_at->diffInDays($row->valid_until));
            $until = $until->format('Y-m-d');
        }

        return ['issued_at' => $start->format('Y-m-d'), 'valid_until' => $until];
    }

    /**
     * Delete a row: it goes to the history (action "deleted").
     */
    public function delete(int $workerId, int $id): self
    {
        $row = WorkerCertificate::where('worker_id', $workerId)->find($id);
        if ($row === NULL) return $this->setErrorMessage(translator('The entry does not exist.'));

        try {
            DB::transaction(function () use ($row) {
                WorkerCertificateHistory::archive($row, WorkerCertificateHistory::ACTION_DELETED, Auth::id());
                $row->delete();
            });
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }
        return $this->setSuccessMessage(translator('Deleted! The entry is kept in the history.'));
    }

    /**
     * History of the worker, newest first.
     *
     * @return Collection<WorkerCertificateHistory>
     */
    public static function history(int $workerId): Collection
    {
        return WorkerCertificateHistory::where('worker_id', $workerId)->orderByDesc('archived_at')->orderByDesc('id')->get();
    }
}
