<?php

namespace App\Livewire\Modules\AppSettings;

use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Services\Application\Acl\ManageAclService;

/**
 * App control center -> Access control (new ACL).
 * Container with the roles, permissions and user access tabs.
 */
class Acl extends LivewireController
{
    #[Url('tab')]
    public $acltab = NULL;

    public function mount()
    {
        $tab = $this->acltab;
        $this->setTabs([
            'roles'       => translator('Roles'),
            'permissions' => translator('Permissions'),
            'users'       => translator('User access'),
        ]);
        if ($tab && array_key_exists($tab, $this->tabs)) $this->activeTab = $tab;
        $this->acltab = $this->activeTab;
    }

    public function selectTab($tabID): void
    {
        if (!array_key_exists($tabID, $this->tabs)) return;
        $this->activeTab = $this->acltab = $tabID;
    }

    /**
     * Copy the old ACL (role groups, resources, assignments) into the new one. Can be run again safely.
     *
     * @return void
     */
    public function importFromLegacy()
    {
        try {
            $stats = ManageAclService::importFromLegacy();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('Imported') . ': '
            . $stats['roles'] . ' ' . translator('roles') . ', '
            . $stats['permissions'] . ' ' . translator('permissions') . ', '
            . $stats['role_permissions'] . ' ' . translator('role permissions') . ', '
            . $stats['user_roles'] . ' ' . translator('user roles') . ', '
            . $stats['user_permissions'] . ' ' . translator('user permissions') . ', '
            . $stats['routes'] . ' ' . translator('routes'));
        $this->updateDOMKey();
    }

    public function render()
    {
        return view('livewire.modules.app-settings.acl');
    }
}
