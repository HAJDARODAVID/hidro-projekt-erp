<?php

namespace App\Traits;

use Livewire\Attributes\Locked;
use App\Services\Application\Acl\AclService;

trait TabTrait
{
    /**Locked: the browser may not add tabs (e.g. one the user has no permission for) */
    #[Locked]
    public $tabs = [];
    public $activeTab = 0;

    /**
     * Set the tabs of the component. A tab is either a title, or an array with a title and
     * the ACL permission needed to see it. Tabs the user may not see are left out.
     *
     *   'basic-info'   => 'Basic info',
     *   'payroll-info' => ['title' => 'Payroll', 'permission' => 'employees.worker-info.payroll'],
     *
     * @param array $tabs
     * @return self
     */
    public function setTabs(array $tabs)
    {
        $this->tabs = $this->filterTabsByPermission($tabs);
        $this->activeTab = array_key_first($this->tabs) ?? 0;
        return $this;
    }

    /**
     * Drop the tabs the logged in user has no permission for and flatten the rest to [key => title].
     *
     * @param array $tabs
     * @return array
     */
    protected function filterTabsByPermission(array $tabs): array
    {
        $acl = NULL;
        $output = [];
        foreach ($tabs as $key => $tab) {
            if (is_array($tab)) {
                if (!empty($tab['permission'])) {
                    $acl ??= AclService::current();
                    if (!$acl->can($tab['permission'])) continue;
                }
                $tab = $tab['title'] ?? $key;
            }
            $output[$key] = $tab;
        }
        return $output;
    }

    public function selectTab($tabID): void
    {
        if (!array_key_exists($tabID, $this->tabs)) return;
        $this->activeTab = $tabID;
        return;
    }

    /**
     * $activeTab is public and could be set from the browser, so a hidden tab can't be opened that way.
     */
    public function updatedActiveTab($value): void
    {
        if (!array_key_exists($value, $this->tabs)) $this->activeTab = array_key_first($this->tabs) ?? 0;
    }
}
