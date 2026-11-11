<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Models\WorkerModel;
use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use App\Models\Employees\WorkerType;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerAddress;
use App\Models\Employees\WorkerContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Class CreateWorkerService.
 * Creates a new worker of the company (Employees -> Worker info -> "+").
 *
 * The values use the same field names and rules as UpdateWorkerInfoService (basic info), so a value
 * accepted here can later be changed there. The worker is created active, with the default company and
 * label printing on (like the old HR screen); the address and contact rows are created right away,
 * the old HR screens expect them.
 *
 * Usage: CreateWorkerService::init()->execute(['name' => 'Ivan', 'surname' => 'Horvat', 'type' => 'WRK', ...])
 */
class CreateWorkerService extends BaseService
{
    /**The fields that can be given: [field => label] */
    public const FIELDS = [
        'name'      => 'First name',
        'surname'   => 'Last name',
        'type'      => 'Worker type',
        'oib'       => 'OIB',
        'workplace' => 'Workplace - contract',
        'doe'       => 'Date of employment',
        'ced'       => 'Contract expiration',
        'phone'     => 'Phone nr',
        'email'     => 'E-mail',
        'street'    => 'Street',
        'town'      => 'Town',
        'zip'       => 'ZIP',
        'county'    => 'County',
    ];

    public static function init(): self
    {
        return new self();
    }

    /**
     * Types a new worker can get (the super admin type is excluded): [type => label]
     */
    public static function types(): array
    {
        return array_intersect_key(WorkerType::AVAILABLE_TYPES, array_flip(WorkerType::init()->getAllType(TRUE)));
    }

    /**
     * workers.is_worker of a new worker, from its type: on for workers and group leaders.
     * The flag decides who can be picked in the workday diary (and the old HR work-hours report);
     * the Working hours module picks by type itself. Changed later in the old HR workers table.
     */
    public static function inAttendanceByDefault(?string $type): bool
    {
        return in_array($type, [WorkerType::TYPE_WORKER, WorkerType::TYPE_GROUP_LEADER], TRUE);
    }

    /**
     * Workers with the same first and last name (any status), for the "already exists" warning.
     *
     * @return array [['id', 'firstName', 'lastName', 'status']]
     */
    public static function sameName(?string $name, ?string $surname): array
    {
        $name = trim((string) $name);
        $surname = trim((string) $surname);
        if ($name === '' || $surname === '') return [];

        return Worker::where('firstName', $name)->where('lastName', $surname)
            ->whereIn('type', WorkerType::init()->getAllType(TRUE))
            ->orderByDesc('status')->orderBy('id')
            ->get(['id', 'firstName', 'lastName', 'status'])
            ->toArray();
    }

    /**
     * Validate and create the worker.
     *
     * @param array $values [field => value], see FIELDS
     * @return self data: the new Worker
     */
    public function execute(array $values): self
    {
        $values = $this->normalize(array_intersect_key($values, self::FIELDS));

        $validator = Validator::make($values, $this->rules($values), [], array_map(fn ($label) => translator($label), self::FIELDS));
        if ($validator->fails()) return $this->setErrorMessage($validator->errors()->first());

        /**The OIB belongs to one person: a second worker with it is a mistake (or the worker exists already) */
        if ($values['oib'] !== NULL) {
            $owner = Worker::where('OIB', $values['oib'])->first(['id', 'firstName', 'lastName']);
            if ($owner !== NULL) {
                return $this->setErrorMessage(translator('A worker with this OIB exists already') . ': '
                    . str_pad($owner->id, 3, '0', STR_PAD_LEFT) . ' ' . $owner->firstName . ' ' . $owner->lastName);
            }
        }

        try {
            $worker = DB::transaction(function () use ($values) {
                $worker = Worker::create([
                    'firstName'     => $values['name'],
                    'lastName'      => $values['surname'],
                    'company'       => WorkerModel::DEFAULT_COMPANY,
                    'OIB'           => $values['oib'],
                    'workplace_id'  => $values['workplace'],
                    /**The workplace name as text too (old screens, PDFs) */
                    'working_place' => $values['workplace'] ? Workplace::whereKey($values['workplace'])->value('name') : NULL,
                    'doe'           => $values['doe'],
                    'ced'           => $values['ced'],
                    'type'          => $values['type'],
                    'status'        => WorkerStatus::WORKER_STATUS_ACTIVE,
                    'is_worker'     => self::inAttendanceByDefault($values['type']),
                    'print_label'   => TRUE,
                ]);
                WorkerContact::create(['worker_id' => $worker->id, 'mob' => $values['phone'], 'email' => $values['email']]);
                WorkerAddress::create([
                    'worker_id' => $worker->id,
                    'street'    => $values['street'],
                    'town'      => $values['town'],
                    'zip'       => $values['zip'],
                    'county'    => $values['county'],
                ]);
                return $worker;
            });
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }

        return $this->setSuccessMessage(translator('Worker created!'), $worker);
    }

    /**
     * Trim, blanks to NULL; OIB without spaces, e-mail lowercase (like UpdateWorkerInfoService).
     */
    private function normalize(array $values): array
    {
        $clean = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $value = $values[$field] ?? NULL;
            if (is_string($value)) $value = trim($value);
            if ($value === '') $value = NULL;
            $clean[$field] = match ($field) {
                'oib'       => $value === NULL ? NULL : preg_replace('/\s+/', '', (string) $value),
                'workplace' => is_numeric($value) ? (int) $value : $value,
                'email'     => $value === NULL ? NULL : mb_strtolower((string) $value),
                'zip'       => is_numeric($value) ? (int) $value : $value,
                default     => $value,
            };
        }
        return $clean;
    }

    /**
     * Rules: the same as the basic info (UpdateWorkerInfoService::rules()), plus the type.
     */
    private function rules(array $values): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'surname'   => ['required', 'string', 'max:255'],
            'type'      => ['required', 'in:' . implode(',', array_keys(self::types()))],
            'oib'       => ['nullable', 'digits:11'],
            'workplace' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if (!Workplace::where('status', TRUE)->whereKey((int) $value)->exists()) $fail(translator('Select an active workplace.'));
            }],
            'doe'       => array_filter(['nullable', 'date', $values['ced'] ? 'before_or_equal:ced' : NULL]),
            'ced'       => ['nullable', 'date'],
            'phone'     => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\/\s()]+$/'],
            'email'     => ['nullable', 'email', 'max:255'],
            'street'    => ['nullable', 'string', 'max:255'],
            'town'      => ['nullable', 'string', 'max:255'],
            'zip'       => ['nullable', 'integer', 'between:1,99999'],
            'county'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
