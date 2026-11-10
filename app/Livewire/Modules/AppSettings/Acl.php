<?php

namespace App\Livewire\Modules\AppSettings;

use Livewire\Attributes\Url;
use App\Livewire\LivewireController;
use App\Services\Application\Acl\AclSyncService;
use Illuminate\Support\Facades\File;

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
     * Rebuild the ACL from the last sync file: permissions, roles and role permissions become exactly
     * what the file holds (missing ones are deleted). User roles / overrides of what stays are kept.
     *
     * @return void
     */
    public function rebuildAclFromFile()
    {
        try {
            $stats = AclSyncService::rebuildFromFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('ACL rebuilt') . ': '
            . ($stats['permissions_created'] + $stats['roles_created']) . ' ' . translator('created') . ', '
            . ($stats['permissions_updated'] + $stats['roles_updated']) . ' ' . translator('updated') . ', '
            . ($stats['permissions_deleted'] + $stats['roles_deleted']) . ' ' . translator('deleted') . ', '
            . $stats['role_permissions_added'] . ' / ' . $stats['role_permissions_removed'] . ' ' . translator('role permissions added / removed'));
        if (!empty($stats['warnings'])) $this->showException(implode("\n", $stats['warnings']));
        $this->updateDOMKey();
    }

    /**
     * Local env only: export the permissions and roles into the ACL sync file (installers/auto-installations),
     * commit it and the next deploy installs it.
     *
     * @return void
     */
    public function exportAcl()
    {
        if (!app()->isLocal()) return $this->showException(translator('The ACL can only be exported on the local environment.'));

        try {
            $export = AclSyncService::exportToFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('Exported') . ': ' . $export['permissions'] . ' ' . translator('permissions') . ', ' . $export['roles'] . ' ' . translator('roles'));
        if (!empty($export['warnings'])) $this->showException(implode("\n", $export['warnings']));
    }

    /**
     * Not on the local env: create / update the permissions and roles from the ACL sync file.
     *
     * @return void
     */
    public function syncAclFromFile()
    {
        if (app()->isLocal()) return $this->showException(translator('On the local environment the ACL is exported, not synced.'));

        try {
            $stats = AclSyncService::importFromFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->notifyMe(translator('Synced') . ': '
            . ($stats['permissions_created'] ?? 0) . ' ' . translator('permissions created') . ', '
            . ($stats['roles_created'] ?? 0) . ' ' . translator('roles created') . ', '
            . (($stats['permissions_updated'] ?? 0) + ($stats['roles_updated'] ?? 0)) . ' ' . translator('updated'));
        if (!empty($stats['warnings'])) $this->showException(implode("\n", $stats['warnings']));
        $this->updateDOMKey();
    }

    public function render()
    {
        return view('livewire.modules.app-settings.acl', [
            'isLocalEnv'   => app()->isLocal(),
            'syncFileDate' => File::exists(AclSyncService::filePath())
                ? date('d.m.Y H:i', File::lastModified(AclSyncService::filePath()))
                : NULL,
        ]);
    }
}
