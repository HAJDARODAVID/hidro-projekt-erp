<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\File;
use App\Livewire\LivewireController;
use App\Services\Application\Acl\AclService;
use App\Services\Application\CustomizeService;
use App\Services\Config\WorkplaceFieldsConfigService;

/**
 * Employees -> Workplaces: configurator of the company's workplace fields (super admin only).
 *
 * Opened in the global modal (config/global-modal.php, key "workplace-fields-configurator"), which
 * mounts this component fresh on every open, so mount() loads the draft from the file.
 *
 * Two tabs:
 *  - configurator: tabs + fields of the catalog (visible / label / required / tab) and the company's own fields,
 *    edited as a draft and saved to customize/{company}/workplace_fields.json
 *  - preview: the workplace page as the draft would show it (optionally with the hidden tabs / fields dimmed)
 *
 * The draft is kept as rows for the UI and turned into the customize format by buildConfig().
 * Parts of the file the configurator has no UI for (options, help, order) are kept as they are.
 */
class WorkplaceFieldsConfigurator extends LivewireController
{
    /**Event sent to the workplaces page after the file is saved */
    public const SAVED_EVENT = 'workplace-fields-saved';

    /**Tabs of the draft: [tab => ['visible' => bool, 'label' => string]] */
    public $tabRows = [];

    /**Catalog fields of the draft: [field => ['visible' => bool, 'label' => string, 'required' => bool, 'tab' => string]] */
    public $fieldRows = [];

    /**The company's own fields of the draft: [field => ['visible', 'label', 'required', 'tab', 'type']] */
    public $customRows = [];

    /**Form of a new own field */
    public $newCustom = ['key' => '', 'label' => '', 'type' => 'text', 'tab' => 'basic'];

    /**Open options editor: "fieldRows.{field}" / "customRows.{field}", NULL = none */
    public ?string $optionsOpen = NULL;

    /**Selected tab of the preview */
    public $previewTab = 'basic';

    /**Preview: show the hidden tabs / fields too (dimmed) */
    public $previewShowHidden = FALSE;

    /**The draft differs from the file */
    public $dirty = FALSE;

    /**The company's file as it was loaded (base for the parts without UI) */
    #[Locked]
    public $original = [];

    /**The company's file can't be read (invalid JSON): saving would overwrite it, so it is blocked */
    #[Locked]
    public ?string $loadError = NULL;

    /**Params passed in from the global modal (see config/global-modal.php), none needed */
    public array $params = [];

    /**Super admin? Checked again on every action. */
    #[Locked]
    public bool $allowed = FALSE;

    public function mount()
    {
        $this->setTabs([
            'configurator' => translator('Configurator'),
            'preview'      => translator('Preview'),
        ]);
        $this->allowed = $this->isAllowed();
        if ($this->allowed) $this->loadDraft();
    }

    private function isAllowed(): bool
    {
        return AclService::current()->isSuperAdmin();
    }

    private function fieldsService(): WorkplaceFieldsConfigService
    {
        return app(WorkplaceFieldsConfigService::class);
    }

    /**
     * Fill the draft rows from the company's file.
     */
    public function loadDraft(): void
    {
        if (!$this->isAllowed()) return;

        $this->loadError = NULL;
        try {
            $this->original = CustomizeService::get(WorkplaceFieldsConfigService::CUSTOMIZE_FILE) ?? [];
        } catch (\Throwable $th) {
            $this->original = [];
            $this->loadError = $th->getMessage();
        }

        $resolved = $this->fieldsService()->resolve($this->original);
        $visible = [];
        foreach ($resolved['fields'] as $tab => $fields) {
            foreach ($fields as $field => $definition) $visible[$field] = $definition + ['tab' => $tab];
        }

        $this->tabRows = [];
        foreach (WorkplaceFieldsConfigService::TABS as $tab => $label) {
            $change = $this->original['tabs'][$tab] ?? NULL;
            $this->tabRows[$tab] = [
                'visible' => $change !== FALSE,
                'label'   => is_array($change) && isset($change['label']) ? (string) $change['label'] : $label,
            ];
        }

        $this->fieldRows = [];
        foreach (WorkplaceFieldsConfigService::CATALOG as $field => [$no, $tab, $type, $label, $options]) {
            $change = is_array($this->original['fields'][$field] ?? NULL) ? $this->original['fields'][$field] : [];
            $this->fieldRows[$field] = [
                'visible'  => isset($visible[$field]),
                'label'    => (string) ($visible[$field]['label'] ?? $change['label'] ?? $label),
                'required' => (bool) ($visible[$field]['required'] ?? $change['required'] ?? $this->defaultRequired($field)),
                'tab'      => (string) ($visible[$field]['tab'] ?? $change['tab'] ?? $tab),
                /**Options as text, one per line; NULL = the field has no editable options (no list, or the other workplaces) */
                'options'  => $this->hasEditableOptions($field)
                    ? $this->optionsToText($field, $change['options'] ?? $options)
                    : NULL,
            ];
        }

        $this->customRows = [];
        foreach ((array) ($this->original['custom'] ?? []) as $field => $custom) {
            if (isset(WorkplaceFieldsConfigService::CATALOG[$field]) || !is_array($custom)) continue;
            $this->customRows[$field] = [
                'visible'  => ($this->original['fields'][$field] ?? NULL) !== FALSE,
                'label'    => (string) ($custom['label'] ?? $field),
                'required' => (bool) ($custom['required'] ?? FALSE),
                'tab'      => (string) ($custom['tab'] ?? 'basic'),
                'type'     => (string) ($custom['type'] ?? 'text'),
                'options'  => $this->optionsToText($field, $custom['options'] ?? []),
            ];
        }

        $this->reset('newCustom', 'previewShowHidden', 'optionsOpen');
        $this->previewTab = 'basic';
        $this->dirty = FALSE;
    }

    private function defaultRequired(string $field): bool
    {
        return in_array($field, ['key', 'name'], TRUE);
    }

    /* ---------------- Options (select / checks) ---------------- */

    /**Types whose options are a list */
    private const OPTION_TYPES = ['select', 'checks'];

    /**A catalog field with a list of options (reportsTo lists the other workplaces by itself) */
    private function hasEditableOptions(string $field): bool
    {
        $catalog = WorkplaceFieldsConfigService::CATALOG[$field] ?? NULL;
        return $catalog !== NULL && in_array($catalog[2], self::OPTION_TYPES, TRUE) && is_array($catalog[4]);
    }

    private function optionMaxLength(string $field): int
    {
        return WorkplaceFieldsConfigService::OPTION_MAX_LENGTHS[$field] ?? WorkplaceFieldsConfigService::OPTION_MAX_LENGTH;
    }

    /**
     * Clean list of options: trimmed, no blanks, no duplicates, cut to the column length and the max. count.
     *
     * @param string $field
     * @param array|string|null $options list, or text with one option per line
     * @param array $problems filled with what was changed (for the notification)
     * @return array
     */
    private function cleanOptions(string $field, $options, array &$problems = []): array
    {
        if (is_string($options)) $options = preg_split('/\r\n|\r|\n/', $options);
        $maxLength = $this->optionMaxLength($field);

        $clean = [];
        foreach ((array) $options as $option) {
            if (!is_scalar($option)) continue;
            $option = trim(preg_replace('/\s+/u', ' ', (string) $option));
            if ($option === '') continue;
            if (mb_strlen($option) > $maxLength) {
                $option = rtrim(mb_substr($option, 0, $maxLength));
                $problems['length'] = translator('Options longer than') . " $maxLength " . translator('characters were cut.');
            }
            if (in_array($option, $clean, TRUE)) {
                $problems['duplicate'] = translator('Duplicate options were removed.');
                continue;
            }
            $clean[] = $option;
        }
        if (count($clean) > WorkplaceFieldsConfigService::OPTIONS_MAX) {
            $clean = array_slice($clean, 0, WorkplaceFieldsConfigService::OPTIONS_MAX);
            $problems['count'] = translator('Only the first') . ' ' . WorkplaceFieldsConfigService::OPTIONS_MAX . ' ' . translator('options are kept.');
        }
        return $clean;
    }

    private function optionsToText(string $field, $options): string
    {
        return implode("\n", $this->cleanOptions($field, $options));
    }

    /**Default options of a catalog field (the catalog's list) */
    private function defaultOptions(string $field): array
    {
        return (array) (WorkplaceFieldsConfigService::CATALOG[$field][4] ?? []);
    }

    /**
     * Open / close the options editor of a field.
     *
     * @param string $rows "fieldRows" or "customRows"
     * @param string $field
     */
    public function toggleOptions($rows, $field): void
    {
        $key = "$rows.$field";
        if (!in_array($rows, ['fieldRows', 'customRows'], TRUE) || !isset($this->{$rows}[$field])) return;
        $this->optionsOpen = $this->optionsOpen === $key ? NULL : $key;
    }

    /**Options of a catalog field back to the catalog's list */
    public function resetOptions($field): void
    {
        if (!$this->isAllowed() || !isset($this->fieldRows[$field]) || !$this->hasEditableOptions((string) $field)) return;
        $this->fieldRows[$field]['options'] = implode("\n", $this->defaultOptions((string) $field));
        $this->dirty = TRUE;
    }

    /**
     * Visible select / checks fields without any option can't be filled in: saving is refused.
     *
     * @return array field labels
     */
    private function fieldsWithoutOptions(): array
    {
        $empty = [];
        foreach ($this->fieldRows as $field => $row) {
            if ($row['options'] !== NULL && $row['visible'] && trim($row['options']) === '') $empty[] = $row['label'] ?: $field;
        }
        foreach ($this->customRows as $field => $row) {
            if (in_array($row['type'], self::OPTION_TYPES, TRUE) && $row['visible'] && trim((string) $row['options']) === '') $empty[] = $row['label'] ?: $field;
        }
        return $empty;
    }

    /**
     * Turn the draft into the customize format. Only differences to the catalog are written.
     *
     * @param bool $everythingVisible TRUE = ignore "hidden" (for the preview with the hidden parts)
     * @return array
     */
    public function buildConfig(bool $everythingVisible = FALSE): array
    {
        $original = $this->original;
        $config = [];

        /**Tabs */
        $tabs = [];
        foreach ($this->tabRows as $tab => $row) {
            $default = WorkplaceFieldsConfigService::TABS[$tab] ?? NULL;
            if ($default === NULL) continue;
            if (!$row['visible'] && !$everythingVisible && $tab !== 'basic') {
                $tabs[$tab] = FALSE;
            } elseif (trim((string) $row['label']) !== '' && $row['label'] !== $default) {
                $tabs[$tab] = ['label' => trim((string) $row['label'])];
            }
        }

        /**Catalog fields (the "only" list of the file is written as hidden fields) */
        $fields = [];
        foreach ($this->fieldRows as $field => $row) {
            $catalog = WorkplaceFieldsConfigService::CATALOG[$field] ?? NULL;
            if ($catalog === NULL) continue;
            $isCore = in_array($field, WorkplaceFieldsConfigService::CORE_FIELDS, TRUE);
            if (!$row['visible'] && !$isCore && !$everythingVisible) {
                $fields[$field] = FALSE;
                continue;
            }
            /**Keep what has no UI here (help) */
            $change = is_array($original['fields'][$field] ?? NULL) ? $original['fields'][$field] : [];
            unset($change['label'], $change['required'], $change['tab']);
            $label = trim((string) $row['label']);
            if ($label !== '' && $label !== $catalog[3]) $change['label'] = $label;
            if ((bool) $row['required'] !== $this->defaultRequired($field)) $change['required'] = (bool) $row['required'];
            if ($row['tab'] !== $catalog[1]) $change['tab'] = $row['tab'];
            /**Options: only written when they differ from the catalog */
            if ($row['options'] !== NULL) {
                unset($change['options']);
                $options = $this->cleanOptions($field, $row['options']);
                if ($options !== $this->defaultOptions($field)) $change['options'] = $options;
            }
            if (!empty($change)) $fields[$field] = $change;
        }

        /**The company's own fields */
        $custom = [];
        foreach ($this->customRows as $field => $row) {
            $definition = is_array($original['custom'][$field] ?? NULL) ? $original['custom'][$field] : [];
            $definition = array_merge($definition, [
                'tab'   => $row['tab'],
                'label' => trim((string) $row['label']) !== '' ? trim((string) $row['label']) : $field,
                'type'  => $row['type'],
            ]);
            if ($row['required']) $definition['required'] = TRUE;
            else unset($definition['required']);
            if (in_array($row['type'], self::OPTION_TYPES, TRUE)) $definition['options'] = $this->cleanOptions($field, (string) $row['options']);
            else unset($definition['options']);
            $custom[$field] = $definition;
            if (!$row['visible'] && !$everythingVisible) $fields[$field] = FALSE;
        }

        if (!empty($tabs)) $config['tabs'] = $tabs;
        if (!empty($fields)) $config['fields'] = $fields;
        if (!empty($custom)) $config['custom'] = $custom;
        if (!empty($original['order'])) $config['order'] = $original['order'];

        return $config;
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'tabRows') || str_starts_with($property, 'fieldRows') || str_starts_with($property, 'customRows')) {
            $this->dirty = TRUE;
        }

        /**Options typed in the editor: show them cleaned (fieldRows.{field}.options / customRows.{field}.options) */
        if (preg_match('/^(fieldRows|customRows)\.([A-Za-z0-9]+)\.options$/', $property, $match) && isset($this->{$match[1]}[$match[2]])) {
            [, $rows, $field] = $match;
            if ($rows === 'fieldRows' && !$this->hasEditableOptions($field)) {
                $this->fieldRows[$field]['options'] = NULL;
                return;
            }
            $problems = [];
            $this->{$rows}[$field]['options'] = implode("\n", $this->cleanOptions($field, (string) $this->{$rows}[$field]['options'], $problems));
            if (!empty($problems)) $this->notifyMe(implode(' ', $problems), 'warning');
        }

        /**An own field that becomes a select / checks opens its options editor */
        if (preg_match('/^customRows\.([A-Za-z0-9]+)\.type$/', $property, $match) && isset($this->customRows[$match[1]])) {
            if (in_array($this->customRows[$match[1]]['type'], self::OPTION_TYPES, TRUE)) $this->optionsOpen = "customRows.{$match[1]}";
        }
    }

    public function updatedNewCustom($value, $field): void
    {
        if ($field === 'key') $this->newCustom['key'] = $this->customKey((string) $value);
        if ($field === 'label' && trim((string) $this->newCustom['key']) === '') {
            $this->newCustom['key'] = $this->customKey(\Illuminate\Support\Str::ascii((string) $value));
        }
    }

    /**Key of an own field: camelCase letters / digits, like the catalog keys */
    private function customKey(string $value): string
    {
        return \Illuminate\Support\Str::camel(preg_replace('/[^a-z0-9]+/i', ' ', $value));
    }

    public function addCustomField(): void
    {
        if (!$this->isAllowed()) return;

        $key = $this->customKey((string) $this->newCustom['key']);
        $type = (string) $this->newCustom['type'];
        $tab = (string) $this->newCustom['tab'];

        if ($key === '' || !preg_match('/^[a-z][a-zA-Z0-9]*$/', $key)) {
            $this->showException(translator('The key of the field must start with a letter and have only letters and digits.'));
            return;
        }
        if (isset(WorkplaceFieldsConfigService::CATALOG[$key]) || isset($this->customRows[$key])) {
            $this->showException(translator('A field with this key exists already!'));
            return;
        }
        if (!in_array($type, WorkplaceFieldsConfigService::FIELD_TYPES, TRUE) || !isset(WorkplaceFieldsConfigService::TABS[$tab]) || $tab === 'workers') {
            $this->showException(translator('Unknown type or tab!'));
            return;
        }

        $this->customRows[$key] = [
            'visible'  => TRUE,
            'label'    => trim((string) $this->newCustom['label']) !== '' ? trim((string) $this->newCustom['label']) : $key,
            'required' => FALSE,
            'tab'      => $tab,
            'type'     => $type,
            'options'  => '',
        ];
        $this->reset('newCustom');
        $this->dirty = TRUE;
        if (in_array($type, self::OPTION_TYPES, TRUE)) $this->optionsOpen = "customRows.$key";
    }

    public function removeCustomField($field): void
    {
        if (!$this->isAllowed()) return;
        unset($this->customRows[$field]);
        $this->dirty = TRUE;
    }

    /**
     * Write the draft to customize/{company}/workplace_fields.json.
     * The values already saved in workplaces.custom_fields stay in the database.
     */
    public function save(): void
    {
        if (!$this->isAllowed()) {
            $this->showException(translator('Only a super admin can customize the workplace fields!'));
            return;
        }
        if ($this->loadError !== NULL) {
            $this->showException(translator('The customization file is not valid JSON, fix it by hand first.'));
            return;
        }
        $withoutOptions = $this->fieldsWithoutOptions();
        if (!empty($withoutOptions)) {
            $this->showException(translator('These fields need at least one option') . ': ' . implode(', ', $withoutOptions));
            return;
        }

        $path = $this->fieldsService()->companyFilePath();
        $config = ['$comment' => $this->original['$comment'] ?? $this->fileComment($path)] + $this->buildConfig();
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        try {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $json);
        } catch (\Throwable $th) {
            $this->showException(translator('The customization file could not be saved') . ': ' . $th->getMessage());
            return;
        }

        $this->loadDraft();
        $this->dispatch(self::SAVED_EVENT);
        $this->notifyMe(translator('The customization of the workplace fields is saved.'));
    }

    /**The $comment of the file: kept from the file, a new one otherwise */
    private function fileComment(string $path): string
    {
        $data = File::exists($path) ? json_decode((string) File::get($path), TRUE) : NULL;
        return (string) ($data['$comment'] ?? CustomizeService::currentCompany() . '. Format: customize/README.md, example: customize/_example/workplace_fields.json');
    }

    public function selectPreviewTab($tab): void
    {
        $this->previewTab = (string) $tab;
    }

    public function render()
    {
        $data = ['config' => [], 'preview' => NULL, 'json' => '', 'companyFile' => '', 'company' => ''];

        if ($this->allowed) {
            $service = $this->fieldsService();
            $config = $this->buildConfig();
            $draft = $service->resolve($config);
            $shown = $this->previewShowHidden ? $service->resolve($this->buildConfig(TRUE)) : $draft;

            /**Tabs / fields of the preview that the draft hides */
            $visibleFields = [];
            foreach ($draft['fields'] as $fields) $visibleFields += $fields;

            $previewTabs = $shown['tabs'];
            if (!array_key_exists($this->previewTab, $previewTabs)) $this->previewTab = array_key_first($previewTabs) ?? 'basic';

            $data = [
                'config'        => $config,
                'warnings'      => $draft['warnings'],
                'json'          => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'previewTabs'   => $previewTabs,
                'previewFields' => $shown['fields'],
                'hiddenTabs'    => array_keys(array_diff_key($shown['tabs'], $draft['tabs'])),
                'hiddenFields'  => array_keys(array_diff_key(array_merge(...array_values($shown['fields']) ?: [[]]), $visibleFields)),
                'fieldCount'    => count($visibleFields),
                'company'       => CustomizeService::currentCompany(),
                'companyFile'   => str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $service->companyFilePath()),
            ];
        }

        return view('livewire.modules.employees.components.workplace-fields-configurator', $data + [
            'allTabs'    => WorkplaceFieldsConfigService::TABS,
            'fieldTypes' => WorkplaceFieldsConfigService::FIELD_TYPES,
            'catalog'    => WorkplaceFieldsConfigService::CATALOG,
            'coreFields' => WorkplaceFieldsConfigService::CORE_FIELDS,
            'optionMaxLengths' => WorkplaceFieldsConfigService::OPTION_MAX_LENGTHS,
            'optionMaxLength'  => WorkplaceFieldsConfigService::OPTION_MAX_LENGTH,
        ]);
    }
}
