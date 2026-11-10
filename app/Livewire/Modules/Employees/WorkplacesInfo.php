<?php

namespace App\Livewire\Modules\Employees;

use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Services\Config\WorkplaceFieldsConfigService;
use App\Services\Application\CustomizeService;

/**
 * Employees -> Workplaces (contracts).
 *
 * PROTOTYPE: hardcoded example data, nothing is written to the database.
 * The fields come from WorkplaceFieldsConfigService: the full catalog, customized per company
 * with the app config "workplace_fields" (JSON). To try it out, the prototype has a few example
 * company profiles next to the real config. Changes on the screen only live until the page is reloaded.
 */
class WorkplacesInfo extends LivewireController
{
    /**
     * Example company profiles (the JSON a company would put into the "workplace_fields" app config).
     * "company" = the real config of this installation.
     */
    public const PROFILES = [
        'company' => 'This company (customize folder)',
        'full'    => 'Example: everything (no config)',
        'minimal' => 'Example: company 1 - only a few fields',
        'custom'  => 'Example: company 2 - everything + own fields',
    ];

    #[Url('profile')]
    public $profile = 'company';

    #[Url('search')]
    public $workplaceSearch = NULL;

    /**Selected workplace (key) */
    #[Url('workplace')]
    public $selectedWorkplace = NULL;

    /**Hardcoded example workplaces [key => fields + workers] */
    public $workplaces = [];

    /**Form of the new workplace */
    public $newWorkplace = ['key' => '', 'name' => ''];

    /**Show the JSON of the active profile */
    public $showConfig = FALSE;

    public function mount()
    {
        if (!array_key_exists((string) $this->profile, self::PROFILES)) $this->profile = 'company';
        $this->workplaces = $this->examples();
        if (!array_key_exists((string) $this->selectedWorkplace, $this->workplaces)) $this->selectedWorkplace = array_key_first($this->workplaces);
        $this->applyProfile();
    }

    public function updatedProfile(): void
    {
        if (!array_key_exists((string) $this->profile, self::PROFILES)) $this->profile = 'company';
        $this->applyProfile();
    }

    /**
     * Rebuild the tabs for the active profile, keep the tab if it still exists,
     * and give every workplace an (empty) value for fields it doesn't have yet (custom fields).
     */
    private function applyProfile(): void
    {
        $resolved = $this->resolved();
        $active = $this->activeTab;
        $this->setTabs(array_map(fn ($label) => translator($label), $resolved['tabs']));
        if (array_key_exists($active, $this->tabs)) $this->activeTab = $active;

        foreach ($this->workplaces as $key => $workplace) {
            foreach ($resolved['fields'] as $definitions) {
                foreach ($definitions as $field => $definition) {
                    if (!array_key_exists($field, $workplace)) $this->workplaces[$key][$field] = $this->emptyValue($definition['type']);
                }
            }
        }
    }

    /**
     * The config of the active profile. NULL = the real config of this installation.
     */
    private function profileConfig(): ?array
    {
        return match ($this->profile) {
            'full'    => [],
            'minimal' => [
                'only'   => ['department', 'description', 'contractType', 'weeklyHours', 'hourRate', 'fixRate'],
                'fields' => [
                    'department'  => ['label' => 'Odjel', 'options' => ['Uprava', 'Teren']],
                    'description' => ['label' => 'Opis posla', 'required' => TRUE],
                    'hourRate'    => ['tab' => 'basic'],
                    'fixRate'     => ['tab' => 'basic'],
                ],
                'tabs'   => ['workers' => FALSE],
            ],
            'custom'  => [
                'tabs'   => ['pay' => ['label' => 'Plaća i dodaci']],
                'fields' => [
                    'nkz'      => ['required' => TRUE],
                    'licences' => ['options' => ['Vozačka B', 'Vozačka C', 'Vozačka C+E', 'Viličar', 'Rad na visini', 'Zavarivač', 'Bager', 'Dizalica']],
                ],
                'custom' => [
                    'uniformSize'   => ['tab' => 'requirements', 'label' => 'Radna odjeća - veličina', 'type' => 'select', 'options' => ['S', 'M', 'L', 'XL', 'XXL']],
                    'tachograph'    => ['tab' => 'requirements', 'label' => 'Kartica za tahograf', 'type' => 'switch', 'help' => 'Vozači teretnih vozila'],
                    'siteAllowance' => ['tab' => 'pay', 'label' => 'Dnevnica za teren [€]', 'type' => 'number'],
                    'contractNotes' => ['tab' => 'contract', 'label' => 'Posebne odredbe ugovora', 'type' => 'textarea'],
                ],
                'order'  => ['basic' => ['name', 'key', 'department', 'description']],
            ],
            default   => NULL,
        };
    }

    private function resolved(): array
    {
        return app(WorkplaceFieldsConfigService::class)->resolve($this->profileConfig());
    }

    public function selectWorkplace($key)
    {
        $this->selectedWorkplace = $this->selectedWorkplace === $key ? NULL : $key;
    }

    public function resetWorkplaceSearch()
    {
        $this->reset('workplaceSearch');
    }

    public function toggleConfig()
    {
        $this->showConfig = !$this->showConfig;
    }

    /**
     * Prototype: adds the workplace to the list on the screen only.
     */
    public function createWorkplace()
    {
        $key = strtoupper(trim((string) $this->newWorkplace['key']));
        $name = trim((string) $this->newWorkplace['name']);
        if ($key === '' || $name === '') return $this->showException(translator('Enter the key and the name.'));
        if (isset($this->workplaces[$key])) return $this->showException(translator('A workplace with this key already exists!'));

        $this->workplaces[$key] = $this->blank($key, $name);
        $this->applyProfile();
        $this->reset('newWorkplace');
        $this->selectedWorkplace = $key;
        $this->selectTab('basic');
        $this->notifyMe(translator('Prototype: added on the screen only, nothing is saved.'), 'warning');
    }

    public function render()
    {
        $search = mb_strtolower(trim((string) $this->workplaceSearch));
        $list = array_filter($this->workplaces, fn ($w) => $search === ''
            || str_contains(mb_strtolower($w['key']), $search)
            || str_contains(mb_strtolower($w['name']), $search));

        $resolved = $this->resolved();
        $config = $this->profileConfig();
        $fieldsService = app(WorkplaceFieldsConfigService::class);
        $companyConfig = $this->profile === 'company' ? $fieldsService->getCompanyConfig() : NULL;

        return view('livewire.modules.employees.workplaces-info', [
            'list'             => $list,
            'fields'           => $resolved['fields'],
            'warnings'         => $resolved['warnings'],
            'profiles'         => self::PROFILES,
            'configJson'       => $this->profile === 'company'
                ? (empty($companyConfig) ? NULL : json_encode($companyConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                : json_encode((object) $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'fieldCount'       => array_sum(array_map('count', $resolved['fields'])),
            'company'          => CustomizeService::currentCompany(),
            'companyFile'      => str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $fieldsService->companyFilePath()),
            'selected'         => $this->workplaces[$this->selectedWorkplace] ?? NULL,
            'workplaceOptions' => array_map(fn ($w) => $w['key'] . ' - ' . $w['name'], $this->workplaces),
        ]);
    }

    private function emptyValue(string $type)
    {
        return match ($type) {
            'switch' => FALSE,
            'checks' => [],
            'range'  => ['min' => NULL, 'max' => NULL],
            default  => NULL,
        };
    }

    /**
     * An empty workplace with every catalog field.
     */
    private function blank(string $key, string $name): array
    {
        $workplace = [];
        foreach (WorkplaceFieldsConfigService::CATALOG as $field => [, , $type]) {
            $workplace[$field] = $this->emptyValue($type);
        }
        return array_merge($workplace, ['key' => $key, 'name' => $name, 'active' => TRUE, 'workers' => []]);
    }

    /**
     * Example workplaces of a construction / installation company.
     */
    private function examples(): array
    {
        $examples = [
            ['key' => 'RM-DIR', 'name' => 'Direktor', 'department' => 'Uprava', 'reportsTo' => NULL, 'nkz' => '1120.01',
                'description' => "Vodi poslovanje društva, zastupa društvo, odgovara za planiranje i financije.", 'headcount' => 1, 'active' => TRUE,
                'contractType' => 'Neodređeno', 'duration' => NULL, 'probation' => 0, 'notice' => 30, 'weeklyHours' => 40, 'schedule' => 'Klizno radno vrijeme', 'location' => 'Ured + teren', 'specialConditions' => FALSE,
                'education' => 'VSS', 'experience' => 10, 'licences' => ['Vozačka B'], 'safetyTraining' => TRUE, 'medicalCheck' => 0, 'ppe' => [],
                'payGrade' => 'V', 'hourRate' => ['min' => NULL, 'max' => NULL], 'fixRate' => 3500, 'fieldBonus' => FALSE, 'homeBonus' => FALSE, 'travelExpense' => 0, 'phoneExpense' => 30, 'monthlyBonus' => FALSE, 'overtime' => FALSE,
                'workers' => [['id' => 1, 'name' => 'Ivan Horvat', 'since' => '2015-03-01', 'contract' => 'Neodređeno']]],

            ['key' => 'RM-VG', 'name' => 'Voditelj gradilišta', 'department' => 'Gradilište', 'reportsTo' => 'RM-DIR', 'nkz' => '3123.01',
                'description' => "Organizira i nadzire radove na gradilištu, vodi građevinski dnevnik, koordinira grupe i kooperante.", 'headcount' => 3, 'active' => TRUE,
                'contractType' => 'Neodređeno', 'duration' => NULL, 'probation' => 6, 'notice' => 30, 'weeklyHours' => 40, 'schedule' => 'Terenski rad', 'location' => 'Teren / gradilište', 'specialConditions' => FALSE,
                'education' => 'VŠS', 'experience' => 5, 'licences' => ['Vozačka B', 'Prva pomoć'], 'safetyTraining' => TRUE, 'medicalCheck' => 24, 'ppe' => ['Kaciga', 'Zaštitne cipele', 'Reflektirajući prsluk'],
                'payGrade' => 'IV', 'hourRate' => ['min' => 9.5, 'max' => 13], 'fixRate' => NULL, 'fieldBonus' => TRUE, 'homeBonus' => FALSE, 'travelExpense' => 50, 'phoneExpense' => 20, 'monthlyBonus' => TRUE, 'overtime' => TRUE,
                'workers' => [
                    ['id' => 7, 'name' => 'Marko Kovačević', 'since' => '2018-06-01', 'contract' => 'Neodređeno'],
                    ['id' => 12, 'name' => 'Ana Babić', 'since' => '2022-02-14', 'contract' => 'Neodređeno'],
                ]],

            ['key' => 'RM-MON-KV', 'name' => 'Monter vodovoda i kanalizacije (KV)', 'department' => 'Gradilište', 'reportsTo' => 'RM-VG', 'nkz' => '7126.21',
                'description' => "Montaža, spajanje i ispitivanje cjevovoda, priprema rova, rad po projektnoj dokumentaciji.", 'headcount' => 12, 'active' => TRUE,
                'contractType' => 'Neodređeno', 'duration' => NULL, 'probation' => 3, 'notice' => 14, 'weeklyHours' => 40, 'schedule' => 'Terenski rad', 'location' => 'Teren / gradilište', 'specialConditions' => TRUE,
                'education' => 'KV', 'experience' => 2, 'licences' => ['Vozačka B', 'Rad na visini'], 'safetyTraining' => TRUE, 'medicalCheck' => 24, 'ppe' => ['Kaciga', 'Zaštitne cipele', 'Rukavice', 'Reflektirajući prsluk'],
                'payGrade' => 'III', 'hourRate' => ['min' => 7, 'max' => 9.5], 'fixRate' => NULL, 'fieldBonus' => TRUE, 'homeBonus' => TRUE, 'travelExpense' => 0, 'phoneExpense' => 0, 'monthlyBonus' => TRUE, 'overtime' => TRUE,
                'workers' => [
                    ['id' => 21, 'name' => 'Josip Novak', 'since' => '2019-04-01', 'contract' => 'Neodređeno'],
                    ['id' => 24, 'name' => 'Tomislav Jurić', 'since' => '2021-09-06', 'contract' => 'Neodređeno'],
                    ['id' => 33, 'name' => 'Luka Knežević', 'since' => '2026-05-04', 'contract' => 'Određeno'],
                ]],

            ['key' => 'RM-POM', 'name' => 'Pomoćni radnik', 'department' => 'Gradilište', 'reportsTo' => 'RM-VG', 'nkz' => '9312.01',
                'description' => "Pomoćni poslovi na gradilištu: iskop, prijenos materijala, čišćenje.", 'headcount' => 8, 'active' => TRUE,
                'contractType' => 'Određeno', 'duration' => 6, 'probation' => 1, 'notice' => 7, 'weeklyHours' => 40, 'schedule' => 'Terenski rad', 'location' => 'Teren / gradilište', 'specialConditions' => TRUE,
                'education' => 'NSS', 'experience' => 0, 'licences' => [], 'safetyTraining' => TRUE, 'medicalCheck' => 24, 'ppe' => ['Kaciga', 'Zaštitne cipele', 'Rukavice', 'Reflektirajući prsluk'],
                'payGrade' => 'I', 'hourRate' => ['min' => 5.5, 'max' => 7], 'fixRate' => NULL, 'fieldBonus' => TRUE, 'homeBonus' => TRUE, 'travelExpense' => 0, 'phoneExpense' => 0, 'monthlyBonus' => FALSE, 'overtime' => TRUE,
                'workers' => [['id' => 41, 'name' => 'Stjepan Pavlović', 'since' => '2026-03-02', 'contract' => 'Određeno']]],

            ['key' => 'RM-VOZ-C', 'name' => 'Vozač teretnog vozila (C)', 'department' => 'Vozni park', 'reportsTo' => 'RM-VG', 'nkz' => '8332.01',
                'description' => "Prijevoz materijala i strojeva, utovar/istovar, briga o vozilu.", 'headcount' => 2, 'active' => TRUE,
                'contractType' => 'Neodređeno', 'duration' => NULL, 'probation' => 3, 'notice' => 14, 'weeklyHours' => 40, 'schedule' => 'Jednokratno (7-15)', 'location' => 'Teren / gradilište', 'specialConditions' => TRUE,
                'education' => 'SSS', 'experience' => 3, 'licences' => ['Vozačka C', 'Vozačka C+E', 'Viličar'], 'safetyTraining' => TRUE, 'medicalCheck' => 12, 'ppe' => ['Zaštitne cipele', 'Reflektirajući prsluk'],
                'payGrade' => 'III', 'hourRate' => ['min' => 7.5, 'max' => 9], 'fixRate' => NULL, 'fieldBonus' => TRUE, 'homeBonus' => FALSE, 'travelExpense' => 0, 'phoneExpense' => 10, 'monthlyBonus' => TRUE, 'overtime' => TRUE,
                'workers' => [['id' => 15, 'name' => 'Damir Vuković', 'since' => '2017-11-20', 'contract' => 'Neodređeno']]],

            ['key' => 'RM-ADM', 'name' => 'Administrativni referent', 'department' => 'Ured', 'reportsTo' => 'RM-DIR', 'nkz' => '4110.01',
                'description' => "Uredsko poslovanje, evidencija radnog vremena, priprema dokumentacije za obračun plaća.", 'headcount' => 2, 'active' => TRUE,
                'contractType' => 'Neodređeno', 'duration' => NULL, 'probation' => 3, 'notice' => 14, 'weeklyHours' => 40, 'schedule' => 'Jednokratno (7-15)', 'location' => 'Ured', 'specialConditions' => FALSE,
                'education' => 'SSS', 'experience' => 1, 'licences' => [], 'safetyTraining' => TRUE, 'medicalCheck' => 0, 'ppe' => [],
                'payGrade' => 'II', 'hourRate' => ['min' => NULL, 'max' => NULL], 'fixRate' => 1250, 'fieldBonus' => FALSE, 'homeBonus' => FALSE, 'travelExpense' => 40, 'phoneExpense' => 0, 'monthlyBonus' => TRUE, 'overtime' => FALSE,
                'workers' => [['id' => 5, 'name' => 'Petra Marić', 'since' => '2020-01-13', 'contract' => 'Neodređeno']]],

            ['key' => 'RM-STU', 'name' => 'Student - pomoć na gradilištu', 'department' => 'Gradilište', 'reportsTo' => 'RM-VG', 'nkz' => '9312.01',
                'description' => "Sezonska pomoć na gradilištu preko studentskog ugovora.", 'headcount' => 0, 'active' => FALSE,
                'contractType' => 'Studentski ugovor', 'duration' => 3, 'probation' => 0, 'notice' => 0, 'weeklyHours' => 20, 'schedule' => 'Jednokratno (7-15)', 'location' => 'Teren / gradilište', 'specialConditions' => FALSE,
                'education' => 'SSS', 'experience' => 0, 'licences' => [], 'safetyTraining' => TRUE, 'medicalCheck' => 0, 'ppe' => ['Kaciga', 'Zaštitne cipele', 'Reflektirajući prsluk'],
                'payGrade' => 'I', 'hourRate' => ['min' => 5, 'max' => 6], 'fixRate' => NULL, 'fieldBonus' => FALSE, 'homeBonus' => FALSE, 'travelExpense' => 0, 'phoneExpense' => 0, 'monthlyBonus' => FALSE, 'overtime' => FALSE,
                'workers' => []],
        ];

        $output = [];
        foreach ($examples as $example) {
            $output[$example['key']] = array_merge($this->blank($example['key'], $example['name']), $example);
        }
        return $output;
    }
}
