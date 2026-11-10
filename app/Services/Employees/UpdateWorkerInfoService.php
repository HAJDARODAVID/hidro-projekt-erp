<?php

namespace App\Services\Employees;

use App\Services\BaseService;
use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use App\Models\Employees\WorkerAddress;
use App\Models\Employees\WorkerContact;
use Illuminate\Support\Facades\Validator;

/**
 * Class UpdateWorkerInfoService.
 * Saves one field of the basic worker info (Employees -> Worker info -> Basic info).
 * Each field is validated and written to its own table:
 *   workers:        name, surname, oib, doe, ced, workplace, comment
 *   worker_contact: phone, email      (row created when missing)
 *   worker_address: street, town, zip, county (row created when missing)
 * The saved (normalized) value is returned in the data: ['field' => ..., 'value' => ...].
 */
class UpdateWorkerInfoService extends BaseService
{
    /**Field of the form => [table group, column] */
    public const FIELDS = [
        'name'      => ['worker', 'firstName'],
        'surname'   => ['worker', 'lastName'],
        'oib'       => ['worker', 'OIB'],
        'doe'       => ['worker', 'doe'],
        'ced'       => ['worker', 'ced'],
        'workplace' => ['worker', 'working_place'],
        'comment'   => ['worker', 'comment'],
        'phone'     => ['contact', 'mob'],
        'email'     => ['contact', 'email'],
        'street'    => ['address', 'street'],
        'town'      => ['address', 'town'],
        'zip'       => ['address', 'zip'],
        'county'    => ['address', 'county'],
    ];

    /** @var Worker|null */
    private $worker;

    public function __construct(int $workerID)
    {
        $this->worker = Worker::find($workerID);
    }

    /**
     * Validate and save one field.
     *
     * @param string $field one of FIELDS
     * @param mixed $value
     * @return self
     */
    public function updateField(string $field, $value): self
    {
        if ($this->worker === NULL) return $this->setErrorMessage(translator('Worker not found!'));
        if (!array_key_exists($field, self::FIELDS)) return $this->setErrorMessage(translator('This field can not be changed!'));

        $value = $this->normalize($field, $value);

        $validator = Validator::make([$field => $value], [$field => $this->rules($field)], [], [$field => translator($this->label($field))]);
        if ($validator->fails()) return $this->setErrorMessage($validator->errors()->first($field));

        try {
            [$group, $column] = self::FIELDS[$field];
            match ($group) {
                'worker'  => $this->worker->update([$column => $value]),
                'contact' => WorkerContact::updateOrCreate(['worker_id' => $this->worker->id], [$column => $value]),
                'address' => WorkerAddress::updateOrCreate(['worker_id' => $this->worker->id], [$column => $value]),
            };
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }

        return $this->setSuccessMessage(translator('Worker info saved!'), ['field' => $field, 'value' => $value]);
    }

    /**
     * Trim the value and turn blanks into NULL; OIB without spaces, zip as a number.
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function normalize(string $field, $value)
    {
        if (is_string($value)) $value = trim($value);
        if ($value === '' || $value === NULL) return NULL;

        return match ($field) {
            'oib'   => preg_replace('/\s+/', '', (string) $value),
            'zip'   => is_numeric($value) ? (int) $value : $value,
            'email' => mb_strtolower((string) $value),
            default => $value,
        };
    }

    /**
     * Validation rules of a field.
     *
     * @param string $field
     * @return array
     */
    private function rules(string $field): array
    {
        return match ($field) {
            'name', 'surname'          => ['required', 'string', 'max:255'],
            'oib'                      => ['nullable', 'digits:11'],
            'doe'                      => array_filter(['nullable', 'date', $this->worker->ced ? 'before_or_equal:' . $this->worker->ced : NULL]),
            'ced'                      => array_filter(['nullable', 'date', $this->worker->doe ? 'after_or_equal:' . $this->worker->doe : NULL]),
            'workplace'                => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                // Only active workplaces can be picked, the current value may stay
                if ($value !== $this->worker->working_place && !Workplace::where('status', TRUE)->where('name', $value)->exists()) {
                    $fail(translator('Select an active workplace.'));
                }
            }],
            'comment'                  => ['nullable', 'string', 'max:255'],
            'phone'                    => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\/\s()]+$/'],
            'email'                    => ['nullable', 'email', 'max:255'],
            'zip'                      => ['nullable', 'integer', 'between:1,99999'],
            'street', 'town', 'county' => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * Label of a field, for the validation messages.
     *
     * @param string $field
     * @return string
     */
    private function label(string $field): string
    {
        return [
            'name' => 'Name', 'surname' => 'Surname', 'oib' => 'OIB', 'doe' => 'Date of employment',
            'ced' => 'Contract expiration', 'workplace' => 'Workplace - contract', 'comment' => 'Comment',
            'phone' => 'Phone nr', 'email' => 'E-mail', 'street' => 'Street', 'town' => 'Town',
            'zip' => 'ZIP', 'county' => 'County',
        ][$field];
    }
}
