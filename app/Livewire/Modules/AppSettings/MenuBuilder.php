<?php

namespace App\Livewire\Modules\AppSettings;

use App\Livewire\LivewireController;
use App\Models\Application\AppMenu;
use App\Models\Application\AppMenuItem;
use App\Models\Application\AppModule;
use App\Models\User;
use App\Services\Application\Acl\AclService;
use App\Services\Application\CompanyContext;
use App\Services\Application\Menu\AppMenusSyncService;
use App\Services\Application\Menu\MenuBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;

/**
 * Menu builder: build the menus rendered with <x-ui.app-menu /> (tree of groups, links, headers, dividers).
 * Every change is saved right away.
 */
class MenuBuilder extends LivewireController
{
    /** Bootstrap icons offered in the icon picker (any other icon name can be typed) */
    public const ICONS = [
        'house', 'speedometer2', 'grid', 'app-indicator', 'people', 'person', 'person-badge', 'person-gear',
        'building', 'briefcase', 'clipboard-data', 'clipboard-check', 'calendar3', 'clock', 'stopwatch', 'alarm',
        'cash-coin', 'wallet2', 'receipt', 'calculator', 'graph-up', 'bar-chart', 'pie-chart', 'table',
        'box-seam', 'boxes', 'truck', 'car-front', 'tools', 'wrench', 'hammer', 'gear',
        'file-earmark-text', 'folder2', 'archive', 'journal-text', 'list-check', 'check2-square', 'tags', 'bookmark',
        'envelope', 'chat-dots', 'bell', 'shield-lock', 'key', 'lock', 'globe', 'motherboard',
    ];

    #[Url('menu')]
    public ?int $menuId = NULL;

    public ?int $selectedItemId = NULL;

    /** Fields of the selected item */
    public array $form = [];

    /** Fields of the selected menu */
    public array $menuForm = [];

    public bool $showNewMenu = FALSE;

    public array $newMenu = ['name' => '', 'key' => ''];

    /** Right panel: settings | preview */
    public string $panel = 'settings';

    /** Preview the menu as this user (NULL = me) */
    public $previewUserId = NULL;

    /**
     * Livewire requests skip the route middleware: check the super admin on every request.
     */
    public function boot()
    {
        abort_unless(AclService::current()->isSuperAdmin(), 403);
    }

    public function mount()
    {
        $menu = $this->menuId ? $this->menus()->firstWhere('id', $this->menuId) : NULL;
        $menu ??= $this->menus()->firstWhere('key', AppMenu::KEY_MAIN) ?? $this->menus()->first();
        $this->loadMenu($menu);
    }

    /* ----------------------------------------------------------------------------------------------
     | Menus
     * --------------------------------------------------------------------------------------------*/

    public function selectMenu($id)
    {
        $this->loadMenu($this->menus()->firstWhere('id', (int) $id));
    }

    public function toggleNewMenu()
    {
        $this->showNewMenu = !$this->showNewMenu;
        $this->newMenu = ['name' => '', 'key' => ''];
    }

    public function createMenu()
    {
        $this->run(function () {
            $menu = $this->service()->createMenu((string) $this->newMenu['name'], (string) $this->newMenu['key'], $this->companyId());
            $this->showNewMenu = FALSE;
            $this->newMenu = ['name' => '', 'key' => ''];
            $this->loadMenu($menu);
            $this->notifyMe(translator('Menu created'));
        });
    }

    public function updatedMenuForm($value, $key)
    {
        $this->run(function () use ($value, $key) {
            $this->service()->updateMenu($this->menu(), $key, $value);
        }, fn () => $this->loadMenu($this->menu()));
    }

    public function deleteMenu()
    {
        $this->run(function () {
            $this->service()->deleteMenu($this->menu());
            $this->loadMenu($this->menus()->first());
            $this->notifyMe(translator('Menu deleted'));
        });
    }

    /* ----------------------------------------------------------------------------------------------
     | Adding items: inside the selected group, else right after the selected item, else at the end
     * --------------------------------------------------------------------------------------------*/

    public function addItem(string $type)
    {
        $this->run(function () use ($type) {
            [$parentId, $afterId] = $this->insertTarget();
            $item = $this->service()->addItem($this->menu(), $type, [], $parentId, $afterId);
            $this->focusItem($item);
        });
    }

    public function addRoute(string $routeName)
    {
        $this->run(function () use ($routeName) {
            [$parentId, $afterId] = $this->insertTarget();
            $item = $this->service()->addRoute($this->menu(), $routeName, $parentId, $afterId);
            $this->focusItem($item);
        });
    }

    public function addModule(string $moduleId)
    {
        $this->run(function () use ($moduleId) {
            [$parentId, $afterId] = $this->insertTarget();
            $item = $this->service()->addModule($this->menu(), $moduleId, $parentId, $afterId);
            $this->focusItem($item);
        });
    }

    public function importLegacyMenu()
    {
        $this->run(function () {
            $stats = $this->service()->importLegacyMenu($this->menu());
            $this->notifyMe(translator('Imported') . ': ' . $stats['groups'] . ' ' . translator('groups') . ', ' . $stats['links'] . ' ' . translator('links'));
            if ($stats['skipped']) {
                $this->showException(translator('These routes do not exist (or need parameters) and were skipped') . ': ' . implode(', ', array_unique($stats['skipped'])));
            }
        });
    }

    /* ----------------------------------------------------------------------------------------------
     | Editing items
     * --------------------------------------------------------------------------------------------*/

    /**
     * Click on an item: select it, a click on the selected item unselects it.
     */
    public function selectItem($id)
    {
        $this->run(function () use ($id) {
            $item = $this->item((int) $id);
            if ($this->selectedItemId === $item->id && $this->panel === 'settings') {
                $this->clearSelection();
                return;
            }
            $this->focusItem($item);
        });
    }

    public function clearSelection()
    {
        $this->selectedItemId = NULL;
        $this->form = [];
    }

    public function updatedForm($value, $key)
    {
        if ($key === 'link_kind' || !$this->selectedItemId) return; // UI only

        $this->run(function () use ($value, $key) {
            $item = $this->service()->updateItem($this->item($this->selectedItemId), $key, $value);
            $this->fillForm($item);
        }, fn () => $this->selectedItemId && $this->fillForm($this->item($this->selectedItemId)));
    }

    public function pickIcon(string $icon)
    {
        $this->form['icon'] = $icon;
        $this->updatedForm($icon, 'icon');
    }

    public function toggleActive($id)
    {
        $this->run(function () use ($id) {
            $item = $this->item((int) $id);
            $item = $this->service()->updateItem($item, 'active', !$item->active);
            if ($this->selectedItemId === $item->id) $this->fillForm($item);
        });
    }

    public function duplicateItem($id)
    {
        $this->run(function () use ($id) {
            $copy = $this->service()->duplicateItem($this->item((int) $id));
            $this->focusItem($copy);
        });
    }

    public function deleteItem($id)
    {
        $this->run(function () use ($id) {
            $item = $this->item((int) $id);
            $this->service()->deleteItem($item);
            if ($this->selectedItemId === $item->id || !AppMenuItem::whereKey($this->selectedItemId)->exists()) {
                $this->selectedItemId = NULL;
                $this->form = [];
            }
        });
    }

    /* ----------------------------------------------------------------------------------------------
     | Moving items
     * --------------------------------------------------------------------------------------------*/

    /**
     * Drag & drop. $targetId NULL = the end of the menu, $zone: before | after | inside.
     */
    public function moveItem($id, $targetId, string $zone)
    {
        $this->run(fn () => $this->service()->move($this->item((int) $id), $targetId ? $this->item((int) $targetId) : NULL, $zone));
    }

    public function moveUp($id)
    {
        $this->run(fn () => $this->service()->moveUp($this->item((int) $id)));
    }

    public function moveDown($id)
    {
        $this->run(fn () => $this->service()->moveDown($this->item((int) $id)));
    }

    public function indent($id)
    {
        $this->run(fn () => $this->service()->indent($this->item((int) $id)));
    }

    public function outdent($id)
    {
        $this->run(fn () => $this->service()->outdent($this->item((int) $id)));
    }

    /* ----------------------------------------------------------------------------------------------
     | Sync between environments (same as the modules and the ACL)
     * --------------------------------------------------------------------------------------------*/

    public function exportMenus()
    {
        if (!app()->isLocal()) return $this->showException(translator('The menus can only be exported on the local environment.'));

        try {
            $export = AppMenusSyncService::exportToFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
        $this->notifyMe(translator('Exported') . ': ' . $export['menus'] . ' ' . translator('menus') . ', ' . $export['items'] . ' ' . translator('items'));
    }

    public function syncMenusFromFile()
    {
        if (app()->isLocal()) return $this->showException(translator('On the local environment the menus are exported, not synced.'));

        try {
            $stats = AppMenusSyncService::importFromFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }

        $this->loadMenu($this->menus()->firstWhere('id', $this->menuId) ?? $this->menus()->first());
        $this->notifyMe(translator('Synced') . ': '
            . ($stats['menus_created'] ?? 0) . ' ' . translator('menus created') . ', '
            . ($stats['menus_updated'] ?? 0) . ' ' . translator('updated'));
    }

    /* ----------------------------------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------------------------------*/

    private function service(): MenuBuilderService
    {
        return app(MenuBuilderService::class);
    }

    private function companyId(): ?int
    {
        return CompanyContext::idOf(Auth::user());
    }

    private function menus()
    {
        return $this->service()->menus($this->companyId());
    }

    private function menu(): AppMenu
    {
        $menu = $this->menuId ? AppMenu::ofCompany($this->companyId())->find($this->menuId) : NULL;
        if (!$menu) throw new \InvalidArgumentException(translator('The menu does not exist (anymore).'));
        return $menu;
    }

    private function item(int $id): AppMenuItem
    {
        $item = AppMenuItem::where('menu_id', $this->menu()->id)->find($id);
        if (!$item) throw new \InvalidArgumentException(translator('The item does not exist (anymore).'));
        return $item;
    }

    private function loadMenu(?AppMenu $menu): void
    {
        $this->menuId = $menu?->id;
        $this->menuForm = $menu ? ['name' => $menu->name, 'key' => $menu->key, 'active' => $menu->active] : [];
        $this->selectedItemId = NULL;
        $this->form = [];
    }

    private function fillForm(AppMenuItem $item): void
    {
        $this->form = [
            'type'       => $item->type,
            'title'      => $item->title,
            'icon'       => $item->icon,
            'route_name' => $item->route_name,
            'url'        => $item->url,
            'link_kind'  => $item->url ? 'url' : 'route',
            'new_tab'    => $item->new_tab,
            'permission' => $item->permission,
            'active'     => $item->active,
        ];
    }

    private function focusItem(AppMenuItem $item): void
    {
        $this->selectedItemId = $item->id;
        $this->panel = 'settings';
        $this->fillForm($item);
    }

    /**
     * @return array [parentId, afterId]
     */
    private function insertTarget(): array
    {
        if (!$this->selectedItemId) return [NULL, NULL];
        $selected = AppMenuItem::where('menu_id', $this->menuId)->find($this->selectedItemId);
        if (!$selected) return [NULL, NULL];
        return $selected->isGroup() ? [$selected->id, NULL] : [NULL, $selected->id];
    }

    /**
     * Run a change; a validation problem is shown to the user, then $onError runs (e.g. reload the form).
     */
    private function run(callable $change, ?callable $onError = NULL): void
    {
        try {
            $change();
        } catch (\InvalidArgumentException $e) {
            if ($onError) {
                try {
                    $onError();
                } catch (\InvalidArgumentException) {
                    $this->loadMenu(NULL);
                }
            }
            $this->showException($e->getMessage());
        }
    }

    public function render()
    {
        $menu = $this->menuId ? AppMenu::ofCompany($this->companyId())->find($this->menuId) : NULL;
        $service = $this->service();

        $selected = $menu && $this->selectedItemId ? AppMenuItem::where('menu_id', $menu->id)->find($this->selectedItemId) : NULL;
        $previewUser = $this->previewUserId ? User::find((int) $this->previewUserId) : NULL;

        return view('livewire.modules.app-settings.menu-builder', [
            'menus'          => $this->menus(),
            'menu'           => $menu,
            'tree'           => $menu ? $service->tree($menu) : [],
            'catalog'        => $service->routeCatalog(),
            'appModules'     => AppModule::orderBy('name')->get(['id', 'name', 'active']),
            'linkableRoutes' => $service->linkableRoutes(),
            'permissionKeys' => DB::table('acl_permissions')->orderBy('key')->pluck('name', 'key'),
            'users'          => User::orderBy('name')->orderBy('last_name')->get(['id', 'name', 'last_name', 'active']),
            'types'          => AppMenuItem::TYPES,
            'icons'          => self::ICONS,
            'selected'       => $selected,
            'problems'       => $selected ? $service->problems($selected, \App\Services\Application\Menu\MenuRenderer::appRoutes()) : [],
            'previewUser'    => $previewUser,
            'maxDepth'       => MenuBuilderService::MAX_DEPTH,
            'isLocalEnv'     => app()->isLocal(),
            'syncFileDate'   => File::exists(AppMenusSyncService::filePath()) ? date('d.m.Y H:i', File::lastModified(AppMenusSyncService::filePath())) : NULL,
            'hasLegacyMenu'  => \App\Models\ModuleItemModel::exists(),
        ]);
    }
}
