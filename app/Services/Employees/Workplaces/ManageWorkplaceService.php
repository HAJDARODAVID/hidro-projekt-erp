<?php

namespace App\Services\Employees\Workplaces;

use App\Services\BaseService;
use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Config\WorkplaceFieldsConfigService;

/**
 * Class ManageWorkplaceService.
 * Creates, changes and deletes workplaces (contracts).
 *
 * A field can only be changed when the company's customization shows it
 * (customize/{company}/workplace_fields.json), and is validated by its type and definition:
 * required, the options of a select / checks, the number limits, a range's min <= max.
 */
class ManageWorkplaceService extends BaseService
{
    /**Workplace keys: uppercase words split by - or _, e.g. RM-MON-KV */
    public const KEY_PATTERN = '/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/';

    /**
     * Create a workplace (active).
     *
     * @param string $key
     * @param string $name
     * @return self data: the new Workplace
     */
    public function create(string $key, string $name): self
    {
        $key = self::normalizeKey($key);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        $validator = Validator::make(['key' => $key, 'name' => $name], [
            'key'  => ['required', 'max:50', 'regex:' . self::KEY_PATTERN, 'unique:workplaces,key'],
            'name' => ['required', 'max:255'],
        ], [
            'key.regex' => translator('The key may only hold letters, numbers, - and _.'),
        ], ['key' => translator('Key / code'), 'name' => translator('Name')]);
        if ($validator->fails()) return $this->setErrorMessage($validator->errors()->first());

        try {
            $workplace = Workplace::create(['key' => $key, 'name' => $name, 'status' => TRUE]);
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }
        return $this->setSuccessMessage(translator('Workplace created') . ': ' . $key, $workplace);
    }

    /**
     * Validate and save one field.
     *
     * @param int $id workplace
     * @param string $field field name (catalog or custom)
     * @param mixed $value for a range: ['min' => ..., 'max' => ...]
     * @return self data: ['field' => ..., 'value' => the saved value as the form needs it]
     */
    public function updateField(int $id, string $field, $value): self
    {
        $workplace = Workplace::find($id);
        if ($workplace === NULL) return $this->setErrorMessage(translator('Workplace not found!'));

        $definition = app(WorkplaceFieldsConfigService::class)->visibleFields()[$field] ?? NULL;
        if ($definition === NULL) return $this->setErrorMessage(translator('This field is not used by the company and can not be changed.'));

        $value = $this->normalize($definition, $field, $value);
        $error = $this->validate($workplace, $definition, $field, $value);
        if ($error !== NULL) return $this->setErrorMessage($error);

        try {
            DB::transaction(function () use ($workplace, $definition, $field, $value) {
                if ($definition['custom']) {
                    $custom = $workplace->custom_fields ?? [];
                    $custom[$field] = $value;
                    $workplace->custom_fields = $custom;
                } else {
                    $column = WorkplaceFieldsConfigService::COLUMNS[$field];
                    if (is_array($column)) {
                        $workplace->{$column['min']} = $value['min'];
                        $workplace->{$column['max']} = $value['max'];
                    } else {
                        $workplace->{$column} = $field === 'reportsTo' ? ($value === NULL ? NULL : (int) $value) : $value;
                    }
                }
                $workplace->save();

                /**The workers keep the name of their workplace as text too (old screens, PDFs) */
                if ($field === 'name') Worker::where('workplace_id', $workplace->id)->update(['working_place' => $value]);
            });
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }

        return $this->setSuccessMessage(translator('Workplace saved!'), [
            'field' => $field,
            'value' => $field === 'reportsTo' ? (string) ($value ?? '') : GetWorkplacesService::formValue($definition['type'], $value),
        ]);
    }

    /**
     * Delete a workplace. Only when no worker is on it, otherwise it has to be switched off.
     *
     * @param int $id
     * @return self
     */
    public function delete(int $id): self
    {
        $workplace = Workplace::find($id);
        if ($workplace === NULL) return $this->setErrorMessage(translator('Workplace not found!'));

        $workers = Worker::where('workplace_id', $id)->count();
        if ($workers > 0) {
            return $this->setErrorMessage(translator('The workplace has workers') . " ($workers), " . translator('switch it off instead of deleting it.'));
        }

        try {
            $workplace->delete();
        } catch (\Throwable $th) {
            return $this->setErrorMessage($th->getMessage());
        }
        return $this->setSuccessMessage(translator('Workplace deleted!'));
    }

    public static function normalizeKey(string $key): string
    {
        return trim(preg_replace('/[^A-Z0-9_]+/', '-', strtoupper(trim($key))), '-');
    }

    /**
     * Trim, blanks to NULL, numbers to numbers, checks to a clean list.
     */
    private function normalize(array $definition, string $field, $value)
    {
        if ($field === 'key') return self::normalizeKey((string) $value);

        switch ($definition['type']) {
            case 'switch':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'checks':
                return array_values(array_unique(array_filter((array) $value, fn ($item) => $item !== '' && $item !== NULL && $item !== FALSE)));
            case 'range':
                $value = is_array($value) ? $value : [];
                return [
                    'min' => $this->normalizeScalar($value['min'] ?? NULL, TRUE),
                    'max' => $this->normalizeScalar($value['max'] ?? NULL, TRUE),
                ];
            case 'number':
                return $this->normalizeScalar($value, TRUE);
            default:
                return $this->normalizeScalar($value, FALSE);
        }
    }

    private function normalizeScalar($value, bool $number)
    {
        if (is_string($value)) $value = trim($value);
        if ($value === '' || $value === NULL) return NULL;
        if ($number && is_string($value)) $value = str_replace(',', '.', $value);
        return ($number && is_numeric($value)) ? $value + 0 : $value;
    }

    /**
     * @return string|null the error, NULL when valid
     */
    private function validate(Workplace $workplace, array $definition, string $field, $value): ?string
    {
        $label = translator($definition['label']);
        $empty = $value === NULL || $value === [] || (is_array($value) && $definition['type'] === 'range' && $value['min'] === NULL && $value['max'] === NULL);
        if ($definition['required'] && $empty) return $label . ': ' . translator('this field is required.');
        if ($empty) return NULL;

        $options = is_array($definition['options']) ? $definition['options'] : [];
        [$min, $max, $integer] = WorkplaceFieldsConfigService::NUMBER_LIMITS[$field] ?? [NULL, NULL, FALSE];

        switch ($definition['type']) {
            case 'text':
                if ($field === 'key') {
                    if (strlen($value) > 50 || !preg_match(self::KEY_PATTERN, $value)) return $label . ': ' . translator('only letters, numbers, - and _ (max. 50).');
                    if (Workplace::where('key', $value)->where('id', '!=', $workplace->id)->exists()) return $label . ': ' . translator('a workplace with this key already exists!');
                    return NULL;
                }
                $limit = $field === 'nkz' ? 20 : 255;
                return mb_strlen((string) $value) > $limit ? "$label: " . translator('max.') . " $limit " . translator('characters.') : NULL;

            case 'textarea':
                return mb_strlen((string) $value) > 5000 ? "$label: " . translator('max.') . ' 5000 ' . translator('characters.') : NULL;

            case 'date':
                return strtotime((string) $value) === FALSE ? "$label: " . translator('enter a valid date.') : NULL;

            case 'number':
                return $this->numberError($label, $value, $min, $max, $integer);

            case 'range':
                foreach (['min', 'max'] as $end) {
                    if ($value[$end] === NULL) continue;
                    $error = $this->numberError($label . ' (' . $end . ')', $value[$end], $min, $max, $integer);
                    if ($error) return $error;
                }
                if ($value['min'] !== NULL && $value['max'] !== NULL && $value['min'] > $value['max']) return "$label: " . translator('min can not be bigger than max.');
                return NULL;

            case 'select':
                if ($definition['options'] === 'workplaces') return $this->reportsToError($workplace, $label, $value);
                return in_array($value, $options, TRUE) ? NULL : "$label: " . translator('select one of the options.');

            case 'checks':
                /**Values already saved stay allowed, even if the company removed the option since */
                $stored = $definition['custom'] ? ($workplace->custom_fields[$field] ?? []) : ($workplace->{WorkplaceFieldsConfigService::COLUMNS[$field]} ?? []);
                $unknown = array_diff($value, $options, (array) $stored);
                return empty($unknown) ? NULL : "$label: " . translator('unknown option') . ' "' . reset($unknown) . '".';

            case 'switch':
            default:
                return NULL;
        }
    }

    private function numberError(string $label, $value, $min, $max, bool $integer): ?string
    {
        if (!is_numeric($value)) return "$label: " . translator('enter a number.');
        if ($integer && floor((float) $value) != (float) $value) return "$label: " . translator('enter a whole number.');
        if ($min !== NULL && $value < $min) return "$label: " . translator('min.') . " $min.";
        if ($max !== NULL && $value > $max) return "$label: " . translator('max.') . " $max.";
        return NULL;
    }

    /**
     * "Reports to" must be another existing workplace, and may not make a circle (A -> B -> A).
     */
    private function reportsToError(Workplace $workplace, string $label, $value): ?string
    {
        $id = (int) $value;
        if ($id === $workplace->id) return "$label: " . translator('a workplace can not report to itself.');
        if (!Workplace::whereKey($id)->exists()) return "$label: " . translator('the workplace does not exist.');

        $seen = [$workplace->id => TRUE];
        for ($current = $id; $current !== NULL; $current = Workplace::whereKey($current)->value('reports_to_id')) {
            if (isset($seen[$current])) return "$label: " . translator('this would make a circle in the hierarchy.');
            $seen[$current] = TRUE;
        }
        return NULL;
    }
}
