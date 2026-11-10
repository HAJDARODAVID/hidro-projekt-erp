<?php

namespace App\Services\Employees\Workplaces;

use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use App\Models\Employees\WorkerStatus;
use App\Services\Config\WorkplaceFieldsConfigService;

/**
 * Class GetWorkplacesService.
 * Reads the workplaces for the Employees -> Workplaces screen.
 */
class GetWorkplacesService
{
    /**
     * Workplaces for the list: id, key, name, status, headcount and the number of employed workers.
     *
     * @param string|null $search key or name
     * @return array
     */
    public static function list(?string $search = NULL): array
    {
        $employed = WorkerStatus::init()->areEmployed();

        return Workplace::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('key', 'LIKE', '%' . $search . '%')->orWhere('name', 'LIKE', '%' . $search . '%')))
            ->withCount(['workers as workers_count' => fn ($q) => $q->whereIn('status', $employed)])
            ->orderBy('status', 'DESC')
            ->orderBy('key')
            ->get(['id', 'key', 'name', 'status', 'headcount'])
            ->toArray();
    }

    /**
     * Options for "reports to": [id => "KEY - name"], without the workplace itself.
     *
     * @param int|null $exceptId
     * @return array
     */
    public static function options(?int $exceptId = NULL): array
    {
        return Workplace::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->orderBy('key')
            ->get(['id', 'key', 'name'])
            ->mapWithKeys(fn (Workplace $w) => [$w->id => $w->key . ' - ' . $w->name])
            ->all();
    }

    /**
     * The values of a workplace by field name (catalog + custom fields), as the form needs them.
     *
     * @param int $id
     * @return array|null NULL when the workplace doesn't exist
     */
    public static function form(int $id): ?array
    {
        $workplace = Workplace::find($id);
        if ($workplace === NULL) return NULL;

        $values = [];
        foreach (WorkplaceFieldsConfigService::COLUMNS as $field => $column) {
            $type = WorkplaceFieldsConfigService::CATALOG[$field][2];
            $values[$field] = is_array($column)
                ? ['min' => $workplace->{$column['min']}, 'max' => $workplace->{$column['max']}]
                : self::formValue($type, $workplace->{$column});
        }
        $values['reportsTo'] = $workplace->reports_to_id ? (string) $workplace->reports_to_id : '';

        /**Custom fields of the company */
        foreach (app(WorkplaceFieldsConfigService::class)->visibleFields() as $field => $definition) {
            if (!$definition['custom']) continue;
            $values[$field] = self::formValue($definition['type'], $workplace->custom_fields[$field] ?? NULL);
        }

        return $values;
    }

    /**
     * Workers on the workplace, employed first.
     *
     * @param int $id
     * @return array
     */
    public static function workers(int $id): array
    {
        $employed = WorkerStatus::init()->areEmployed();

        return Worker::where('workplace_id', $id)
            ->orderByRaw('CASE WHEN status IN (' . implode(',', array_fill(0, count($employed), '?')) . ') THEN 0 ELSE 1 END', $employed)
            ->orderBy('lastName')
            ->get(['id', 'firstName', 'lastName', 'doe', 'ced', 'status'])
            ->map(fn (Worker $worker) => [
                'id'       => $worker->id,
                'name'     => $worker->fullName,
                'doe'      => $worker->doe,
                'ced'      => $worker->ced,
                'status'   => $worker->statusDescriptionCro,
                'employed' => in_array($worker->status, $employed),
            ])
            ->all();
    }

    /**
     * A stored value as the form field type needs it.
     */
    public static function formValue(string $type, $value)
    {
        return match ($type) {
            'switch' => (bool) $value,
            'checks' => is_array($value) ? array_values($value) : [],
            'range'  => is_array($value) ? ['min' => $value['min'] ?? NULL, 'max' => $value['max'] ?? NULL] : ['min' => NULL, 'max' => NULL],
            default  => $value,
        };
    }
}
