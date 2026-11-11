<?php

namespace App\Livewire\Modules\Employees;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Locked;
use App\Livewire\Modules\Employees\Components\WorkplaceFieldsConfigurator;
use App\Livewire\LivewireController;
use App\Services\Application\CustomizeService;
use App\Services\Application\Acl\AclService;
use App\Services\Config\WorkplaceFieldsConfigService;
use App\Services\Employees\Workplaces\GetWorkplacesService;
use App\Services\Employees\Workplaces\ManageWorkplaceService;

/**
 * Employees -> Workplaces (contracts): define the workplaces of the company.
 *
 * The fields come from WorkplaceFieldsConfigService: the full catalog (the workplaces table has every
 * field), customized per company with customize/{company}/workplace_fields.json.
 * Every field saves itself when it is changed (ACL permission employees.workplaces.edit).
 */
class WorkplacesInfo extends LivewireController
{
    /**ACL permission needed to create, change and delete workplaces */
    public const EDIT_PERMISSION = 'employees.workplaces.edit';

    #[Url('search')]
    public $workplaceSearch = null;

    /**Selected workplace (ID) */
    #[Url('workplace')]
    public $selectedWorkplace = null;

    /**Workplaces for the list */
    public $list = [];

    /**Values of the selected workplace [field => value] */
    public $form = [];

    /**Form of the new workplace */
    public $newWorkplace = ['key' => '', 'name' => ''];

    /**Show the customization of the company (file, JSON, warnings) */
    public $showConfig = FALSE;

    /**Can the user change workplaces? Only for the view, the server checks again on every change. */
    #[Locked]
    public bool $canEdit = FALSE;

    /**Super admin: sees the configurator of the fields (WorkplaceFieldsConfigurator). Only for the view, it checks again itself. */
    #[Locked]
    public bool $isSuperAdmin = FALSE;

    public function mount()
    {
        $acl = AclService::current();
        $this->canEdit = $acl->can(self::EDIT_PERMISSION);
        $this->isSuperAdmin = $acl->isSuperAdmin();
        $this->loadTabs();
        $this->getList();

        if ($this->selectedWorkplace) $this->loadForm((int) $this->selectedWorkplace);
    }

    private function loadTabs(): void
    {
        $this->setTabs(array_map(fn($label) => translator($label), $this->fieldsService()->resolve()['tabs']));
    }

    /**
     * The configurator saved the company's file: tabs / fields may have changed.
     * The open tab stays when it still exists.
     */
    #[On(WorkplaceFieldsConfigurator::SAVED_EVENT)]
    public function fieldsCustomized(): void
    {
        $activeTab = $this->activeTab;
        $this->loadTabs();
        $this->selectTab($activeTab);
        if ($this->selectedWorkplace) $this->loadForm((int) $this->selectedWorkplace);
    }

    private function fieldsService(): WorkplaceFieldsConfigService
    {
        return app(WorkplaceFieldsConfigService::class);
    }

    private function getList(): void
    {
        $this->list = GetWorkplacesService::list($this->workplaceSearch ?: null);
    }

    private function loadForm(int $id): void
    {
        $form = GetWorkplacesService::form($id);
        if ($form === null) {
            $this->reset('selectedWorkplace', 'form');
            return;
        }
        $this->selectedWorkplace = $id;
        $this->form = $form;
        $this->saved = [];
    }

    public function updatedWorkplaceSearch($value): void
    {
        if ($value === '') $this->workplaceSearch = null;
        $this->getList();
    }

    public function resetWorkplaceSearch(): void
    {
        $this->reset('workplaceSearch');
        $this->getList();
    }

    public function selectWorkplace($id): void
    {
        if ((string) $this->selectedWorkplace === (string) $id) {
            $this->reset('selectedWorkplace', 'form');
            return;
        }
        $this->loadForm((int) $id);
    }

    public function toggleConfig(): void
    {
        $this->showConfig = !$this->showConfig;
    }

    /**
     * Suggest the key from the name while the key is still empty.
     */
    public function updatedNewWorkplace($value, $field): void
    {
        if ($field === 'name' && trim((string) $this->newWorkplace['key']) === '') {
            $this->newWorkplace['key'] = ManageWorkplaceService::normalizeKey(\Illuminate\Support\Str::ascii((string) $value));
        }
        if ($field === 'key') $this->newWorkplace['key'] = ManageWorkplaceService::normalizeKey((string) $value);
    }

    public function createWorkplace()
    {
        if (!AclService::current()->can(self::EDIT_PERMISSION)) return $this->showException(translator('You are not allowed to change the workplaces!'));

        $service = (new ManageWorkplaceService())->create((string) $this->newWorkplace['key'], (string) $this->newWorkplace['name']);
        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        $this->reset('newWorkplace');
        $this->getList();
        $this->loadForm($service->getResponse()['data']->id);
        $this->selectTab(array_key_first($this->tabs));
        $this->notifyMe($service->getResponse()['message']);
    }

    /**
     * A field of the selected workplace was changed (form.{field} or form.{field}.min / .max for a range).
     * Checks the permission, saves the field and marks it saved (is-valid) or not (is-invalid).
     */
    public function updatedForm($value, $path)
    {
        $field = explode('.', $path, 2)[0];
        $savedKey = 'form.' . $field;
        $this->saved = [];

        if (!$this->selectedWorkplace) return;
        if (!AclService::current()->can(self::EDIT_PERMISSION)) {
            $this->loadForm((int) $this->selectedWorkplace);
            return $this->showException(translator('You are not allowed to change the workplaces!'));
        }

        $service = (new ManageWorkplaceService())->updateField((int) $this->selectedWorkplace, $field, $this->form[$field] ?? null);

        if (!$service->getResponseStatus()) {
            /**Show the saved value again */
            $this->form[$field] = GetWorkplacesService::form((int) $this->selectedWorkplace)[$field] ?? null;
            $this->saved[$savedKey] = FALSE;
            return $this->showException($service->getResponse()['message']);
        }

        $this->form[$field] = $service->getResponse()['data']['value'];
        $this->saved[$savedKey] = TRUE;
        if (in_array($field, ['key', 'name', 'active', 'headcount'], TRUE)) $this->getList();
    }

    public function deleteWorkplace()
    {
        if (!$this->selectedWorkplace) return;
        if (!AclService::current()->can(self::EDIT_PERMISSION)) return $this->showException(translator('You are not allowed to change the workplaces!'));

        $service = (new ManageWorkplaceService())->delete((int) $this->selectedWorkplace);
        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        $this->reset('selectedWorkplace', 'form');
        $this->getList();
        $this->notifyMe($service->getResponse()['message']);
    }

    public function render()
    {
        $fieldsService = $this->fieldsService();
        $resolved = $fieldsService->resolve();
        $companyConfig = $fieldsService->getCompanyConfig();
        $selected = $this->selectedWorkplace ? collect($this->list)->firstWhere('id', (int) $this->selectedWorkplace) : null;

        return view('livewire.modules.employees.workplaces-info', [
            'fields'          => $resolved['fields'],
            'warnings'        => $resolved['warnings'],
            'selected'        => $selected,
            'reportsToOptions' => $this->selectedWorkplace ? GetWorkplacesService::options((int) $this->selectedWorkplace) : [],
            'workers'         => ($this->selectedWorkplace && $this->activeTab === 'workers') ? GetWorkplacesService::workers((int) $this->selectedWorkplace) : [],
            'company'         => CustomizeService::currentCompany(),
            'companyFile'     => str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $fieldsService->companyFilePath()),
            'configJson'      => empty($companyConfig) ? null : json_encode($companyConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'fieldCount'      => array_sum(array_map('count', $resolved['fields'])),
        ]);
    }
}
