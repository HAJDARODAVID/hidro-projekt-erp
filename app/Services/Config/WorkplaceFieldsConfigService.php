<?php

namespace App\Services\Config;

use App\Services\Application\CustomizeService;

/**
 * Class WorkplaceFieldsConfigService.
 * Which fields the workplaces (contracts) screen shows, per company.
 *
 * The full field catalog is defined here (CATALOG); the database has every field.
 * A company customizes the screen with customize/{company}/workplace_fields.json
 * (see CustomizeService). Without that file every field is shown.
 *
 * Config format (every part is optional):
 * {
 *   "only":   ["department", "description", "contractType"],      // show ONLY these (+ the core fields)
 *   "tabs":   { "requirements": false,                            // hide a whole tab
 *               "pay": { "label": "Plaća" } },                    // rename a tab
 *   "fields": { "nkz": false,                                     // hide a field
 *               "department": { "label": "Odjel",                 // rename
 *                               "help": "...",                    // tooltip
 *                               "required": true,                 // must be filled
 *                               "options": ["Uprava", "Teren"],   // own options (select / checks)
 *                               "tab": "basic" } },               // move to another tab
 *   "custom": { "uniform": { "tab": "requirements", "label": "Radna odjeća",   // company's own field
 *                            "type": "select", "options": ["S", "M", "L"] } },
 *   "order":  { "basic": ["name", "key", "department"] }          // order inside a tab (the rest follows)
 * }
 * Core fields (key, name, active) can't be hidden. Unknown fields / tabs / types are ignored
 * and reported in the warnings.
 */
class WorkplaceFieldsConfigService
{
    /**Tabs in their default order. "workers" is the list of workers, not a form. */
    public const TABS = [
        'basic'        => 'Basic',
        'contract'     => 'Contract',
        'requirements' => 'Requirements',
        'pay'          => 'Pay',
        'workers'      => 'Workers',
    ];

    /**Fields that every company has */
    public const CORE_FIELDS = ['key', 'name', 'active'];

    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'select', 'switch', 'checks', 'range'];

    /**
     * The full catalog: field => [no, tab, type, label, options, help]
     * options: list for select / checks, 'workplaces' = the other workplaces
     */
    public const CATALOG = [
        'key'               => [1,  'basic',        'text',     'Key / code',                   NULL, 'Short unique code, e.g. RM-MON-KV'],
        'name'              => [2,  'basic',        'text',     'Name',                         NULL, 'As written in the contract'],
        'department'        => [3,  'basic',        'select',   'Department',                   ['Uprava', 'Ured', 'Gradilište', 'Skladište', 'Vozni park'], NULL],
        'reportsTo'         => [4,  'basic',        'select',   'Reports to (workplace)',       'workplaces', NULL],
        'nkz'               => [5,  'basic',        'text',     'Occupation code (NKZ)',        NULL, 'Nacionalna klasifikacija zanimanja'],
        'description'       => [6,  'basic',        'textarea', 'Duties / description',         NULL, 'Goes into the contract and the job description'],
        'headcount'         => [7,  'basic',        'number',   'Planned headcount',            NULL, 'How many workers this workplace should have'],
        'active'            => [8,  'basic',        'switch',   'Active',                       NULL, 'Inactive workplaces can not be given to new workers'],
        'contractType'      => [9,  'contract',     'select',   'Default contract type',        ['Neodređeno', 'Određeno', 'Ugovor o djelu', 'Studentski ugovor', 'Agencijski'], NULL],
        'duration'          => [10, 'contract',     'number',   'Default duration [months]',    NULL, 'Only for fixed-term contracts'],
        'probation'         => [11, 'contract',     'number',   'Probation [months]',           NULL, 'Max. 6 months'],
        'notice'            => [12, 'contract',     'number',   'Notice period [days]',         NULL, NULL],
        'weeklyHours'       => [13, 'contract',     'number',   'Weekly hours',                 NULL, '40 = full time'],
        'schedule'          => [14, 'contract',     'select',   'Working schedule',             ['Jednokratno (7-15)', 'Smjenski rad', 'Terenski rad', 'Klizno radno vrijeme'], NULL],
        'location'          => [15, 'contract',     'select',   'Work location',                ['Ured', 'Teren / gradilište', 'Ured + teren', 'Skladište'], NULL],
        'specialConditions' => [16, 'contract',     'switch',   'Special working conditions',   NULL, 'Poslovi s posebnim uvjetima rada (medical checks required)'],
        'education'         => [17, 'requirements', 'select',   'Required education',           ['NSS', 'KV', 'SSS', 'VKV', 'VŠS', 'VSS'], NULL],
        'experience'        => [18, 'requirements', 'number',   'Experience [years]',           NULL, NULL],
        'licences'          => [19, 'requirements', 'checks',   'Licences / certificates',      ['Vozačka B', 'Vozačka C', 'Vozačka C+E', 'Viličar', 'Rad na visini', 'Zavarivač', 'Prva pomoć', 'Zaštita od požara'], NULL],
        'safetyTraining'    => [20, 'requirements', 'switch',   'Safety at work training (ZNR)', NULL, NULL],
        'medicalCheck'      => [21, 'requirements', 'number',   'Medical check every [months]', NULL, '0 = not required'],
        'ppe'               => [22, 'requirements', 'checks',   'Protective equipment (PPE)',   ['Kaciga', 'Zaštitne cipele', 'Rukavice', 'Reflektirajući prsluk', 'Zaštitne naočale', 'Pojas za rad na visini'], NULL],
        'payGrade'          => [23, 'pay',          'select',   'Pay grade',                    ['I', 'II', 'III', 'IV', 'V'], NULL],
        'hourRate'          => [24, 'pay',          'range',    'Hourly rate range [€]',        NULL, 'Suggested when a worker gets this workplace'],
        'fixRate'           => [25, 'pay',          'number',   'Fixed monthly pay [€]',        NULL, 'Instead of the hourly rate, e.g. management'],
        'fieldBonus'        => [26, 'pay',          'switch',   'Field bonus',                  NULL, 'Teren'],
        'homeBonus'         => [27, 'pay',          'switch',   'Home bonus',                   NULL, NULL],
        'travelExpense'     => [28, 'pay',          'number',   'Travel expense [€]',           NULL, 'Default for the payroll'],
        'phoneExpense'      => [29, 'pay',          'number',   'Phone expense [€]',            NULL, 'Default for the payroll'],
        'monthlyBonus'      => [30, 'pay',          'switch',   'Eligible for the monthly bonus', NULL, NULL],
        'overtime'          => [31, 'pay',          'switch',   'Overtime allowed',             NULL, NULL],
    ];

    /**Name of the customization file: customize/{company}/workplace_fields.json */
    public const CUSTOMIZE_FILE = 'workplace_fields';

    /**Problem reading the company's file (e.g. invalid JSON), shown in the warnings */
    private ?string $loadError = NULL;

    /**
     * The company's customization from the customize folder, NULL when it has none (= show everything).
     *
     * @return array|null
     */
    public function getCompanyConfig(): ?array
    {
        $this->loadError = NULL;
        try {
            return CustomizeService::get(self::CUSTOMIZE_FILE);
        } catch (\Throwable $th) {
            $this->loadError = $th->getMessage() . ' - every field is shown until it is fixed.';
            return NULL;
        }
    }

    /**
     * Path of the active company's customization file (it may not exist yet).
     */
    public function companyFilePath(): string
    {
        return CustomizeService::filePath(self::CUSTOMIZE_FILE);
    }

    /**
     * Apply a config to the catalog.
     *
     * @param array|null $config NULL = the company's config (getCompanyConfig()), [] = everything
     * @return array [
     *   'tabs'     => [tab => label]                         (visible tabs, in order),
     *   'fields'   => [tab => [field => definition]]         (visible fields per tab, in order),
     *   'warnings' => [...]                                  (parts of the config that were ignored)
     * ]
     * definition: ['no', 'type', 'label', 'options', 'help', 'required', 'custom']
     */
    public function resolve(?array $config = NULL): array
    {
        $config ??= $this->getCompanyConfig() ?? [];
        $warnings = $this->loadError ? [$this->loadError] : [];

        /**1. The catalog as definitions */
        $definitions = [];
        foreach (self::CATALOG as $field => [$no, $tab, $type, $label, $options, $help]) {
            $definitions[$field] = compact('no', 'tab', 'type', 'label', 'options', 'help') + ['required' => in_array($field, ['key', 'name'], TRUE), 'custom' => FALSE];
        }

        /**2. The company's own fields */
        $no = count(self::CATALOG);
        foreach ((array) ($config['custom'] ?? []) as $field => $custom) {
            if (isset($definitions[$field])) { $warnings[] = "custom.$field: a field with this name exists, use fields.$field to change it."; continue; }
            $type = $custom['type'] ?? 'text';
            if (!in_array($type, self::FIELD_TYPES, TRUE)) { $warnings[] = "custom.$field: unknown type \"$type\", ignored."; continue; }
            $tab = $custom['tab'] ?? 'basic';
            if (!isset(self::TABS[$tab]) || $tab === 'workers') { $warnings[] = "custom.$field: unknown tab \"$tab\", ignored."; continue; }
            $definitions[$field] = [
                'no' => ++$no, 'tab' => $tab, 'type' => $type, 'label' => $custom['label'] ?? $field,
                'options' => $custom['options'] ?? NULL, 'help' => $custom['help'] ?? NULL,
                'required' => (bool) ($custom['required'] ?? FALSE), 'custom' => TRUE,
            ];
        }

        /**3. "only": a whitelist */
        if (isset($config['only'])) {
            $only = array_merge((array) $config['only'], self::CORE_FIELDS, array_keys((array) ($config['custom'] ?? [])));
            foreach (array_diff((array) $config['only'], array_keys($definitions)) as $unknown) $warnings[] = "only: unknown field \"$unknown\".";
            $definitions = array_intersect_key($definitions, array_flip($only));
        }

        /**4. Per field: hide / change */
        foreach ((array) ($config['fields'] ?? []) as $field => $change) {
            if (!isset($definitions[$field])) {
                if (!isset(self::CATALOG[$field])) $warnings[] = "fields.$field: unknown field.";
                continue;
            }
            if ($change === FALSE) {
                if (in_array($field, self::CORE_FIELDS, TRUE)) { $warnings[] = "fields.$field: core field, can't be hidden."; continue; }
                unset($definitions[$field]);
                continue;
            }
            if (!is_array($change)) continue;
            foreach (['label', 'help', 'options'] as $attribute) {
                if (array_key_exists($attribute, $change)) $definitions[$field][$attribute] = $change[$attribute];
            }
            if (array_key_exists('required', $change)) $definitions[$field]['required'] = (bool) $change['required'];
            if (isset($change['tab'])) {
                if (isset(self::TABS[$change['tab']]) && $change['tab'] !== 'workers') $definitions[$field]['tab'] = $change['tab'];
                else $warnings[] = "fields.$field.tab: unknown tab \"{$change['tab']}\".";
            }
        }

        /**5. Tabs: hide / rename */
        $tabs = self::TABS;
        foreach ((array) ($config['tabs'] ?? []) as $tab => $change) {
            if (!isset($tabs[$tab])) { $warnings[] = "tabs.$tab: unknown tab."; continue; }
            if ($change === FALSE) {
                if ($tab === 'basic') { $warnings[] = 'tabs.basic: holds the core fields, can\'t be hidden.'; continue; }
                unset($tabs[$tab]);
            } elseif (is_array($change) && isset($change['label'])) {
                $tabs[$tab] = $change['label'];
            }
        }

        /**6. Group by tab, in order; a tab without fields isn't shown */
        $fields = [];
        foreach ($tabs as $tab => $label) {
            if ($tab === 'workers') continue;
            $inTab = array_filter($definitions, fn ($definition) => $definition['tab'] === $tab);
            $order = array_values(array_intersect((array) ($config['order'][$tab] ?? []), array_keys($inTab)));
            $inTab = array_replace(array_flip($order), $inTab);
            if (empty($inTab)) { unset($tabs[$tab]); continue; }
            $fields[$tab] = $inTab;
        }

        return ['tabs' => $tabs, 'fields' => $fields, 'warnings' => $warnings];
    }
}
