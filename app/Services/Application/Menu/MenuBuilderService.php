<?php

namespace App\Services\Application\Menu;

use App\Models\Application\AppMenu;
use App\Models\Application\AppMenuItem;
use App\Models\Application\AppModule;
use App\Models\Application\AppModuleRoute;
use App\Models\ModuleItemModel;
use App\Models\ModuleItemsRouteModel;
use App\Services\Application\Acl\AclService;
use App\Services\Application\CompanyContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Everything the menu builder changes on a menu.
 *
 * Tree rules:
 *  - only groups have children,
 *  - at most MAX_DEPTH levels (group > group > link),
 *  - positions are 0..n per parent, always renumbered after a change.
 *
 * Invalid input throws \InvalidArgumentException with a message that can be shown to the user.
 * Every write calls MenuRenderer::flush() so the rendered menus are rebuilt.
 */
class MenuBuilderService
{
    public const MAX_DEPTH = 3;

    /** Route names (prefixes) that never make sense as a menu link */
    private const HIDDEN_ROUTE_PREFIXES = ['livewire.', 'debugbar.', 'ignition.', 'sanctum.', 'telescope', 'horizon', 'password.', 'verification.', 'login', 'logout', 'register'];

    /* ----------------------------------------------------------------------------------------------
     | Menus
     * --------------------------------------------------------------------------------------------*/

    public function menus(?int $companyId): Collection
    {
        return AppMenu::ofCompany($companyId)->orderBy('name')->get();
    }

    public function createMenu(string $name, string $key, ?int $companyId): AppMenu
    {
        $name = $this->cleanTitle($name, TRUE);
        $key = $this->cleanKey($key !== '' ? $key : $name);
        $this->assertKeyFree($key, $companyId);

        $menu = AppMenu::create(['name' => $name, 'key' => $key, 'company_id' => $companyId, 'active' => TRUE]);
        MenuRenderer::flush();
        return $menu;
    }

    public function updateMenu(AppMenu $menu, string $field, mixed $value): AppMenu
    {
        switch ($field) {
            case 'name':
                $menu->name = $this->cleanTitle((string) $value, TRUE);
                break;
            case 'key':
                $key = $this->cleanKey((string) $value);
                $this->assertKeyFree($key, $menu->company_id, $menu->id);
                $menu->key = $key;
                break;
            case 'active':
                $menu->active = (bool) $value;
                break;
            default:
                throw new \InvalidArgumentException(translator('Unknown menu field.'));
        }
        $menu->save();
        MenuRenderer::flush();
        return $menu;
    }

    public function deleteMenu(AppMenu $menu): void
    {
        $menu->delete(); // items cascade
        MenuRenderer::flush();
    }

    /* ----------------------------------------------------------------------------------------------
     | Items
     * --------------------------------------------------------------------------------------------*/

    /**
     * Add an item: inside $parentId (must be a group), right after $afterId, or at the end of the menu.
     */
    public function addItem(AppMenu $menu, string $type, array $attributes = [], ?int $parentId = NULL, ?int $afterId = NULL): AppMenuItem
    {
        if (!array_key_exists($type, AppMenuItem::TYPES)) throw new \InvalidArgumentException(translator('Unknown item type.'));

        [$parentId, $position] = $this->insertPoint($menu, $parentId, $afterId, $type === AppMenuItem::TYPE_GROUP ? 2 : 1);

        return DB::transaction(function () use ($menu, $type, $attributes, $parentId, $position) {
            $this->shiftPositions($menu->id, $parentId, $position);

            $item = new AppMenuItem(['menu_id' => $menu->id, 'parent_id' => $parentId, 'type' => $type, 'position' => $position, 'active' => TRUE]);
            foreach ($attributes as $field => $value) {
                $this->applyField($item, $field, $value);
            }
            if ($item->title === NULL && $type !== AppMenuItem::TYPE_DIVIDER) {
                $item->title = AppMenuItem::TYPES[$type]['label'];
            }
            $item->save();

            MenuRenderer::flush();
            return $item;
        });
    }

    /**
     * A link to a named route, titled after its app module route (or the route name).
     */
    public function addRoute(AppMenu $menu, string $routeName, ?int $parentId = NULL, ?int $afterId = NULL): AppMenuItem
    {
        $this->assertLinkableRoute($routeName);
        $title = AppModuleRoute::where('route_name', $routeName)->value('title') ?? Str::headline($routeName);

        return $this->addItem($menu, AppMenuItem::TYPE_LINK, ['title' => $title, 'route_name' => $routeName], $parentId, $afterId);
    }

    /**
     * A group named after the app module, with a link for every active route of the module.
     */
    public function addModule(AppMenu $menu, string $appModuleId, ?int $parentId = NULL, ?int $afterId = NULL): AppMenuItem
    {
        $module = AppModule::find($appModuleId);
        if (!$module) throw new \InvalidArgumentException(translator('The module does not exist.'));

        return DB::transaction(function () use ($menu, $module, $parentId, $afterId) {
            $group = $this->addItem($menu, AppMenuItem::TYPE_GROUP, ['title' => $module->name], $parentId, $afterId);

            $routes = AppModuleRoute::where('module_id', $module->id)->where('active', TRUE)->orderBy('position')->get();
            $position = 0;
            foreach ($routes as $route) {
                if (!$this->isLinkableRoute($route->route_name)) continue;
                AppMenuItem::create([
                    'menu_id'    => $menu->id,
                    'parent_id'  => $group->id,
                    'type'       => AppMenuItem::TYPE_LINK,
                    'title'      => $this->cleanTitle((string) ($route->title ?: Str::headline($route->route_name))),
                    'route_name' => $route->route_name,
                    'position'   => $position++,
                    'active'     => TRUE,
                ]);
            }

            MenuRenderer::flush();
            return $group;
        });
    }

    /**
     * Copy the old sidebar (module_items / module_item_routes) to the end of the menu:
     * one group per old module, one link per old route. Routes that no longer exist are skipped.
     *
     * @return array ['groups' => int, 'links' => int, 'skipped' => [route names]]
     */
    public function importLegacyMenu(AppMenu $menu): array
    {
        $stats = ['groups' => 0, 'links' => 0, 'skipped' => []];

        DB::transaction(function () use ($menu, &$stats) {
            $routes = ModuleItemsRouteModel::orderBy('id')->get()->groupBy('module_id');

            foreach (ModuleItemModel::orderBy('id')->get() as $module) {
                $links = ($routes[$module->id] ?? collect())->filter(function ($route) use (&$stats) {
                    if ($this->isLinkableRoute((string) $route->route_name)) return TRUE;
                    $stats['skipped'][] = $route->route_name;
                    return FALSE;
                });
                if ($links->isEmpty()) continue;

                $group = $this->addItem($menu, AppMenuItem::TYPE_GROUP, [
                    'title' => Str::limit((string) $module->name, 100, ''),
                    'icon'  => $this->cleanIcon((string) $module->icon, FALSE),
                ]);
                $stats['groups']++;

                $position = 0;
                foreach ($links as $route) {
                    AppMenuItem::create([
                        'menu_id'    => $menu->id,
                        'parent_id'  => $group->id,
                        'type'       => AppMenuItem::TYPE_LINK,
                        'title'      => $this->cleanTitle((string) ($route->name ?: $route->route_name)),
                        'route_name' => $route->route_name,
                        'position'   => $position++,
                        'active'     => TRUE,
                    ]);
                    $stats['links']++;
                }
            }
        });

        MenuRenderer::flush();
        return $stats;
    }

    public function updateItem(AppMenuItem $item, string $field, mixed $value): AppMenuItem
    {
        $this->applyField($item, $field, $value);
        $item->save();
        MenuRenderer::flush();
        return $item;
    }

    public function deleteItem(AppMenuItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->delete(); // children cascade
            $this->renumber($item->menu_id, $item->parent_id);
        });
        MenuRenderer::flush();
    }

    /**
     * Copy an item (with its children) right after itself.
     */
    public function duplicateItem(AppMenuItem $item): AppMenuItem
    {
        return DB::transaction(function () use ($item) {
            $this->shiftPositions($item->menu_id, $item->parent_id, $item->position + 1);
            $copy = $this->copyTree($item, $item->parent_id, $item->position + 1);
            MenuRenderer::flush();
            return $copy;
        });
    }

    /* ----------------------------------------------------------------------------------------------
     | Moving
     * --------------------------------------------------------------------------------------------*/

    /**
     * Drag & drop: put $item before / after $target, or inside it (zone "inside", target must be a group).
     * $target NULL = the end of the menu (root).
     */
    public function move(AppMenuItem $item, ?AppMenuItem $target, string $zone): void
    {
        if ($target && $target->menu_id !== $item->menu_id) throw new \InvalidArgumentException(translator('The item belongs to another menu.'));
        if ($target && $target->id === $item->id) return;

        if ($target === NULL) {
            $parentId = NULL;
            $position = PHP_INT_MAX;
        } elseif ($zone === 'inside') {
            if (!$target->isGroup()) throw new \InvalidArgumentException(translator('Items can only be put inside a group.'));
            $parentId = $target->id;
            $position = PHP_INT_MAX;
        } elseif (in_array($zone, ['before', 'after'], TRUE)) {
            $parentId = $target->parent_id;
            $position = $target->position + ($zone === 'after' ? 1 : 0);
        } else {
            throw new \InvalidArgumentException(translator('Unknown drop position.'));
        }

        $this->moveTo($item, $parentId, $position);
    }

    public function moveUp(AppMenuItem $item): void
    {
        if ($item->position > 0) $this->moveTo($item, $item->parent_id, $item->position - 1);
    }

    public function moveDown(AppMenuItem $item): void
    {
        $last = AppMenuItem::where('menu_id', $item->menu_id)->where('parent_id', $item->parent_id)->max('position');
        if ($item->position < $last) $this->moveTo($item, $item->parent_id, $item->position + 2);
    }

    /**
     * Into the group right above the item (as its last child).
     */
    public function indent(AppMenuItem $item): void
    {
        $previous = AppMenuItem::where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->where('position', '<', $item->position)
            ->orderByDesc('position')
            ->first();

        if (!$previous || !$previous->isGroup()) throw new \InvalidArgumentException(translator('There is no group right above this item.'));
        $this->moveTo($item, $previous->id, PHP_INT_MAX);
    }

    /**
     * Out of its group, right after the group.
     */
    public function outdent(AppMenuItem $item): void
    {
        if ($item->parent_id === NULL) return;
        $parent = AppMenuItem::find($item->parent_id);
        $this->moveTo($item, $parent->parent_id, $parent->position + 1);
    }

    /**
     * Put the item at $position (0 based, "before the item now at $position") under $parentId.
     */
    private function moveTo(AppMenuItem $item, ?int $parentId, int $position): void
    {
        if ($parentId !== NULL) {
            $parent = AppMenuItem::where('menu_id', $item->menu_id)->find($parentId);
            if (!$parent || !$parent->isGroup()) throw new \InvalidArgumentException(translator('Items can only be put inside a group.'));
            if ($parent->id === $item->id || $this->isDescendant($parent, $item)) {
                throw new \InvalidArgumentException(translator('A group can not be moved into itself.'));
            }
            // a group needs room for at least one level of children
            $levels = max($this->heightOf($item), $item->isGroup() ? 2 : 1);
            if ($this->depthOf($parent) + $levels > self::MAX_DEPTH) {
                throw new \InvalidArgumentException(self::depthMessage());
            }
        }

        DB::transaction(function () use ($item, $parentId, $position) {
            $oldParentId = $item->parent_id;
            $siblings = AppMenuItem::where('menu_id', $item->menu_id)
                ->where('parent_id', $parentId)
                ->where('id', '!=', $item->id)
                ->orderBy('position')
                ->pluck('id')
                ->all();

            // $position counted the item itself when it stays under the same parent and was above
            if ($oldParentId === $parentId && $item->position < $position) $position--;
            $position = max(0, min($position, count($siblings)));
            array_splice($siblings, $position, 0, [$item->id]);

            $item->parent_id = $parentId;
            $item->save();
            foreach ($siblings as $index => $id) {
                AppMenuItem::where('id', $id)->update(['position' => $index]);
            }
            if ($oldParentId !== $parentId) $this->renumber($item->menu_id, $oldParentId);
        });

        MenuRenderer::flush();
    }

    /* ----------------------------------------------------------------------------------------------
     | Reading (builder)
     * --------------------------------------------------------------------------------------------*/

    /**
     * The whole menu as a nested array (inactive items included) with problems per item.
     */
    public function tree(AppMenu $menu): array
    {
        $items = AppMenuItem::where('menu_id', $menu->id)->orderBy('position')->get();
        $appRoutes = MenuRenderer::appRoutes();
        $byParent = $items->groupBy(fn ($i) => $i->parent_id ?? 0);

        $build = function (int $parentId, int $depth) use (&$build, $byParent, $appRoutes) {
            return ($byParent[$parentId] ?? collect())->map(function (AppMenuItem $item) use (&$build, $depth, $appRoutes) {
                return [
                    'id'         => $item->id,
                    'type'       => $item->type,
                    'title'      => $item->title,
                    'icon'       => $item->icon,
                    'route_name' => $item->route_name,
                    'url'        => $item->url,
                    'new_tab'    => $item->new_tab,
                    'permission' => $item->permission ?: ($item->route_name ? (AclService::routePermissions()[$item->route_name] ?? NULL) : NULL),
                    'own_permission' => (bool) $item->permission,
                    'active'     => $item->active,
                    'depth'      => $depth,
                    'problems'   => $this->problems($item, $appRoutes),
                    'children'   => $item->isGroup() ? $build($item->id, $depth + 1) : [],
                ];
            })->values()->all();
        };

        return $build(0, 1);
    }

    public function problems(AppMenuItem $item, array $appRoutes): array
    {
        $problems = [];
        if ($item->type === AppMenuItem::TYPE_LINK) {
            if (!$item->route_name && !$item->url) $problems[] = translator('No route or URL set, the link is hidden.');
            if ($item->route_name) {
                if (!Route::has($item->route_name)) {
                    $problems[] = translator('The route does not exist, the link is hidden.');
                } elseif (!$this->isLinkableRoute($item->route_name)) {
                    $problems[] = translator('The route needs parameters, the link is hidden.');
                }
                if (isset($appRoutes[$item->route_name]) && !$appRoutes[$item->route_name]['active']) {
                    $problems[] = translator('The route or its module is switched off, the link is hidden.');
                }
            }
        }
        if ($item->permission && !AclService::isKnownPermission($item->permission)) {
            $problems[] = translator('Unknown permission, only super admins see it.');
        }
        return $problems;
    }

    /**
     * Routes that can be put in a menu: named GET routes without required parameters.
     * Registered app module routes come first, grouped by their module.
     *
     * @return array [['module' => string|null, 'module_id' => string|null, 'routes' => [['name','title','uri']]]]
     */
    public function routeCatalog(): array
    {
        $registered = AppModuleRoute::query()
            ->join('app_modules', 'app_modules.id', '=', 'app_module_routes.module_id')
            ->orderBy('app_modules.name')
            ->orderBy('app_module_routes.position')
            ->get(['app_module_routes.route_name', 'app_module_routes.title', 'app_module_routes.active', 'app_modules.id as module_id', 'app_modules.name as module_name', 'app_modules.active as module_active']);

        $all = collect($this->linkableRoutes());
        $groups = [];
        $used = [];

        foreach ($registered->groupBy('module_id') as $moduleId => $routes) {
            $entries = [];
            foreach ($routes as $route) {
                if (!isset($all[$route->route_name])) continue;
                $used[$route->route_name] = TRUE;
                $entries[] = ['name' => $route->route_name, 'title' => $route->title ?: Str::headline($route->route_name), 'uri' => $all[$route->route_name], 'active' => $route->active && $route->module_active];
            }
            if ($entries) $groups[] = ['module' => $routes->first()->module_name, 'module_id' => $moduleId, 'routes' => $entries];
        }

        $other = $all->except(array_keys($used))->sortKeys()->map(fn ($uri, $name) => ['name' => $name, 'title' => Str::headline($name), 'uri' => $uri, 'active' => TRUE])->values()->all();
        if ($other) $groups[] = ['module' => NULL, 'module_id' => NULL, 'routes' => $other];

        return $groups;
    }

    /**
     * @return array<string, string> route name => uri
     */
    public function linkableRoutes(): array
    {
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (!$name || !in_array('GET', $route->methods(), TRUE)) continue;
            if (preg_match('/\{[^?}]+\}/', $route->uri())) continue; // required parameter
            if (Str::startsWith($name, self::HIDDEN_ROUTE_PREFIXES)) continue;
            $routes[$name] = '/' . ltrim($route->uri(), '/');
        }
        return $routes;
    }

    public function isLinkableRoute(string $routeName): bool
    {
        if ($routeName === '' || !Route::has($routeName)) return FALSE;
        $route = Route::getRoutes()->getByName($routeName);
        return in_array('GET', $route->methods(), TRUE) && !preg_match('/\{[^?}]+\}/', $route->uri());
    }

    /* ----------------------------------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------------------------------*/

    private function applyField(AppMenuItem $item, string $field, mixed $value): void
    {
        $isText = in_array($item->type, [AppMenuItem::TYPE_LINK, AppMenuItem::TYPE_GROUP, AppMenuItem::TYPE_HEADER], TRUE);

        switch ($field) {
            case 'title':
                if ($item->type === AppMenuItem::TYPE_DIVIDER) return;
                $item->title = $this->cleanTitle((string) $value, $isText);
                break;
            case 'icon':
                $item->icon = $this->cleanIcon((string) $value);
                break;
            case 'route_name':
                if ($item->type !== AppMenuItem::TYPE_LINK) return;
                $value = trim((string) $value);
                if ($value === '') {
                    $item->route_name = NULL;
                    break;
                }
                $this->assertLinkableRoute($value);
                $item->route_name = $value;
                $item->url = NULL;
                break;
            case 'url':
                if ($item->type !== AppMenuItem::TYPE_LINK) return;
                $value = trim((string) $value);
                if ($value === '') {
                    $item->url = NULL;
                    break;
                }
                // Only http(s) and site relative links: no javascript:, data:, //evil.com ...
                if (mb_strlen($value) > 2048 || !preg_match('~^(https?://[^\s/]+|/(?!/))[^\s]*$~i', $value)) {
                    throw new \InvalidArgumentException(translator('The URL must start with http://, https:// or / (a page of this app).'));
                }
                $item->url = $value;
                $item->route_name = NULL;
                break;
            case 'new_tab':
                $item->new_tab = (bool) $value;
                break;
            case 'permission':
                $value = trim((string) $value);
                if ($value !== '' && !AclService::isKnownPermission($value)) {
                    throw new \InvalidArgumentException(translator('Unknown permission') . ': ' . $value);
                }
                $item->permission = $value === '' ? NULL : $value;
                break;
            case 'active':
                $item->active = (bool) $value;
                break;
            default:
                throw new \InvalidArgumentException(translator('Unknown item field.'));
        }
    }

    private function cleanTitle(string $title, bool $required = FALSE): ?string
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title));
        if ($title === '') {
            if ($required) throw new \InvalidArgumentException(translator('The title is required.'));
            return NULL;
        }
        if (mb_strlen($title) > 100) throw new \InvalidArgumentException(translator('The title can have at most 100 characters.'));
        return $title;
    }

    /**
     * "bi bi-people-fill", "bi-people-fill" and "people-fill" all become "people-fill".
     */
    private function cleanIcon(string $icon, bool $strict = TRUE): ?string
    {
        $icon = strtolower(trim($icon));
        $icon = preg_replace('/^(bi\s+)?bi-/', '', $icon);
        if ($icon === '' || $icon === 'bi') return NULL;
        if (!preg_match('/^[a-z0-9-]{1,60}$/', $icon)) {
            if (!$strict) return NULL;
            throw new \InvalidArgumentException(translator('The icon must be a Bootstrap icon name, e.g. "people".'));
        }
        return $icon;
    }

    private function cleanKey(string $key): string
    {
        $key = Str::slug($key);
        if ($key === '' || strlen($key) > 50) throw new \InvalidArgumentException(translator('The key must have 1 to 50 letters, numbers or dashes.'));
        return $key;
    }

    private function assertKeyFree(string $key, ?int $companyId, ?int $exceptId = NULL): void
    {
        $taken = AppMenu::ofCompany($companyId)->where('key', $key)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();
        if ($taken) throw new \InvalidArgumentException(translator('A menu with this key already exists') . ': ' . $key);
    }

    private function assertLinkableRoute(string $routeName): void
    {
        if (!Route::has($routeName)) throw new \InvalidArgumentException(translator('The route does not exist') . ': ' . $routeName);
        if (!$this->isLinkableRoute($routeName)) throw new \InvalidArgumentException(translator('Only GET routes without parameters can be used in a menu') . ': ' . $routeName);
    }

    /**
     * @return array [parentId, position]
     */
    private function insertPoint(AppMenu $menu, ?int $parentId, ?int $afterId, int $ownHeight): array
    {
        if ($afterId !== NULL) {
            $after = AppMenuItem::where('menu_id', $menu->id)->find($afterId);
            if (!$after) throw new \InvalidArgumentException(translator('The item does not exist.'));
            $parentId = $after->parent_id;
            $position = $after->position + 1;
        } else {
            $position = (int) AppMenuItem::where('menu_id', $menu->id)->where('parent_id', $parentId)->max('position') + 1;
            if (!AppMenuItem::where('menu_id', $menu->id)->where('parent_id', $parentId)->exists()) $position = 0;
        }

        if ($parentId !== NULL) {
            $parent = AppMenuItem::where('menu_id', $menu->id)->find($parentId);
            if (!$parent || !$parent->isGroup()) throw new \InvalidArgumentException(translator('Items can only be put inside a group.'));
            if ($this->depthOf($parent) + $ownHeight > self::MAX_DEPTH) {
                throw new \InvalidArgumentException(self::depthMessage());
            }
        }

        return [$parentId, $position];
    }

    private static function depthMessage(): string
    {
        return str_replace(':n', (string) self::MAX_DEPTH, translator('The menu can be at most :n levels deep.'));
    }

    private function shiftPositions(int $menuId, ?int $parentId, int $from): void
    {
        AppMenuItem::where('menu_id', $menuId)->where('parent_id', $parentId)->where('position', '>=', $from)->increment('position');
    }

    private function renumber(int $menuId, ?int $parentId): void
    {
        $ids = AppMenuItem::where('menu_id', $menuId)->where('parent_id', $parentId)->orderBy('position')->orderBy('id')->pluck('id');
        foreach ($ids as $index => $id) {
            AppMenuItem::where('id', $id)->update(['position' => $index]);
        }
    }

    /** Level of the item, 1 = root */
    private function depthOf(AppMenuItem $item): int
    {
        $depth = 1;
        while ($item->parent_id !== NULL && $depth <= self::MAX_DEPTH + 1) {
            $item = AppMenuItem::find($item->parent_id);
            $depth++;
        }
        return $depth;
    }

    /** Levels the item takes with its children, 1 = no children */
    private function heightOf(AppMenuItem $item): int
    {
        $children = AppMenuItem::where('parent_id', $item->id)->get();
        return 1 + ($children->isEmpty() ? 0 : $children->max(fn ($child) => $this->heightOf($child)));
    }

    /** Is $item somewhere below $ancestor? */
    private function isDescendant(AppMenuItem $item, AppMenuItem $ancestor): bool
    {
        while ($item->parent_id !== NULL) {
            if ($item->parent_id === $ancestor->id) return TRUE;
            $item = AppMenuItem::find($item->parent_id);
        }
        return FALSE;
    }

    private function copyTree(AppMenuItem $item, ?int $parentId, int $position): AppMenuItem
    {
        $copy = $item->replicate(['created_at', 'updated_at']);
        $copy->parent_id = $parentId;
        $copy->position = $position;
        $copy->save();

        foreach ($item->children as $child) {
            $this->copyTree($child, $copy->id, $child->position);
        }
        return $copy;
    }
}
