<?php

namespace App\Services\Application\Menu;

use App\Models\Application\AppMenu;
use App\Models\Application\AppMenuItem;
use App\Models\Translation\AppTranslation;
use App\Models\User;
use App\Services\Application\Acl\AclService;
use App\Services\Application\CompanyContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/**
 * Builds the menu a user may see.
 *
 * A link is shown when
 *  - it and all its parents are active,
 *  - its route exists, needs no parameters, and is not switched off in the app modules,
 *  - the user may open the route (ACL route permission) and holds the item's own permission (if set).
 * A group is shown when at least one link inside it is shown. Headers without anything under them
 * and extra dividers are removed.
 *
 * The raw tree of a menu is cached until the next change (flush()); the ACL check runs per request.
 */
class MenuRenderer
{
    private const VERSION_KEY = 'app_menu.version';

    private AclService $acl;

    private array $appRoutes;

    private function __construct(private ?User $user)
    {
        $this->acl = AclService::forUser($user);
    }

    public static function for(?User $user): self
    {
        return new self($user);
    }

    /**
     * Rebuild every cached menu on the next request. Called after every change in the builder.
     */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    /**
     * The menu by key (the user's company first, then the company-less one) or by ID.
     */
    public function findMenu(string|int $menu): ?AppMenu
    {
        if (is_int($menu) || ctype_digit((string) $menu)) return AppMenu::find((int) $menu);

        $companyId = CompanyContext::idOf($this->user);
        return AppMenu::where('key', $menu)
            ->where(fn ($q) => $companyId === NULL ? $q->whereNull('company_id') : $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->orderByRaw('company_id IS NULL') // own company first
            ->first();
    }

    /**
     * Nested array of the visible items:
     * [['id','type','title','icon','href','new_tab','current','open','children' => [...]], ...]
     *
     * @param string|int|AppMenu $menu key, ID or model
     * @param string|null $currentRoute route name used to mark the current link (default: this request's route)
     * @param bool $ignoreMenuActive render a switched off menu too (builder preview)
     */
    public function tree(string|int|AppMenu $menu, ?string $currentRoute = NULL, bool $ignoreMenuActive = FALSE): array
    {
        $menu = $menu instanceof AppMenu ? $menu : $this->findMenu($menu);
        if (!$menu || (!$menu->active && !$ignoreMenuActive)) return [];

        $this->appRoutes = self::appRoutes();
        $currentRoute ??= request()->route()?->getName();

        $tree = $this->filter($this->rawTree($menu));
        $tree = $this->markCurrent($tree, $currentRoute);
        return $this->translate($tree);
    }

    /**
     * route name => ['active' => route and module active, 'module_id' => ...] for the registered app module routes.
     */
    public static function appRoutes(): array
    {
        try {
            return DB::table('app_module_routes')
                ->join('app_modules', 'app_modules.id', '=', 'app_module_routes.module_id')
                ->whereNotNull('app_module_routes.route_name')
                ->get(['app_module_routes.route_name', 'app_module_routes.active', 'app_modules.active as module_active', 'app_modules.id as module_id'])
                ->mapWithKeys(fn ($r) => [$r->route_name => ['active' => $r->active && $r->module_active, 'module_id' => $r->module_id]])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function rawTree(AppMenu $menu): array
    {
        $key = 'app_menu.v' . (int) Cache::get(self::VERSION_KEY, 1) . '.tree.' . $menu->id;

        return Cache::rememberForever($key, function () use ($menu) {
            $items = AppMenuItem::where('menu_id', $menu->id)->orderBy('position')->get()->groupBy(fn ($i) => $i->parent_id ?? 0);

            $build = function (int $parentId) use (&$build, $items) {
                return ($items[$parentId] ?? collect())->map(fn (AppMenuItem $item) => [
                    'id'         => $item->id,
                    'type'       => $item->type,
                    'title'      => $item->title,
                    'icon'       => $item->icon,
                    'route_name' => $item->route_name,
                    'url'        => $item->url,
                    'new_tab'    => $item->new_tab,
                    'permission' => $item->permission,
                    'active'     => $item->active,
                    'children'   => $item->isGroup() ? $build($item->id) : [],
                ])->values()->all();
            };

            return $build(0);
        });
    }

    private function filter(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $node) {
            if (!$node['active']) continue;
            if ($node['permission'] && !$this->acl->can($node['permission'])) continue;
            $node['current'] = FALSE;
            $node['open'] = FALSE;
            $node['href'] = NULL;

            switch ($node['type']) {
                case AppMenuItem::TYPE_LINK:
                    $node['href'] = $this->href($node);
                    if ($node['href'] === NULL) continue 2;
                    break;
                case AppMenuItem::TYPE_GROUP:
                    $node['children'] = $this->filter($node['children']);
                    if (!$this->hasLink($node['children'])) continue 2;
                    break;
            }
            $out[] = $node;
        }
        return $this->cleanSeparators($out);
    }

    private function href(array $node): ?string
    {
        if ($node['route_name']) {
            $name = $node['route_name'];
            if (!Route::has($name)) return NULL;
            if (isset($this->appRoutes[$name]) && !$this->appRoutes[$name]['active']) return NULL;
            if (!$this->acl->canRoute($name)) return NULL;
            try {
                return route($name);
            } catch (\Throwable) {
                return NULL; // needs parameters
            }
        }
        return $node['url'] ?: NULL;
    }

    private function hasLink(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node['type'] === AppMenuItem::TYPE_LINK || $node['type'] === AppMenuItem::TYPE_GROUP) return TRUE;
        }
        return FALSE;
    }

    /**
     * No leading / trailing / double dividers, no header without anything under it.
     */
    private function cleanSeparators(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $i => $node) {
            if ($node['type'] === AppMenuItem::TYPE_HEADER) {
                // something to show before the next separator?
                $hasContent = FALSE;
                for ($j = $i + 1; $j < count($nodes); $j++) {
                    if (in_array($nodes[$j]['type'], [AppMenuItem::TYPE_DIVIDER, AppMenuItem::TYPE_HEADER], TRUE)) break;
                    $hasContent = TRUE;
                    break;
                }
                if (!$hasContent) continue;
            }
            if ($node['type'] === AppMenuItem::TYPE_DIVIDER) {
                $last = end($out);
                if ($last === FALSE || $last['type'] === AppMenuItem::TYPE_DIVIDER) continue;
            }
            $out[] = $node;
        }
        while ($out && end($out)['type'] === AppMenuItem::TYPE_DIVIDER) array_pop($out);
        return $out;
    }

    /**
     * The link of the current route is "current" and its groups "open". When no link points to the route,
     * a link to another route of the same app module is used (e.g. a module tab that is not in the menu).
     */
    private function markCurrent(array $tree, ?string $currentRoute): array
    {
        if ($currentRoute === NULL || $currentRoute === '') return $tree;

        $match = fn ($node) => $node['route_name'] === $currentRoute;
        if (!$this->find($tree, $match)) {
            $moduleId = $this->appRoutes[$currentRoute]['module_id'] ?? NULL;
            if ($moduleId === NULL) return $tree;
            $match = fn ($node) => $node['route_name'] && ($this->appRoutes[$node['route_name']]['module_id'] ?? NULL) === $moduleId;
        }

        $found = FALSE;
        $mark = function (array $nodes) use (&$mark, $match, &$found) {
            foreach ($nodes as &$node) {
                $node['current'] = FALSE;
                $node['open'] = FALSE;
                if ($node['type'] === AppMenuItem::TYPE_LINK && !$found && $match($node)) {
                    $node['current'] = $found = TRUE;
                }
                if ($node['children']) {
                    $before = $found;
                    $node['children'] = $mark($node['children']);
                    $node['open'] = !$before && $found;
                }
            }
            return $nodes;
        };

        return $mark($tree);
    }

    private function find(array $nodes, callable $match): bool
    {
        foreach ($nodes as $node) {
            if ($node['type'] === AppMenuItem::TYPE_LINK && $match($node)) return TRUE;
            if ($node['children'] && $this->find($node['children'], $match)) return TRUE;
        }
        return FALSE;
    }

    /**
     * All titles in one query (translator() would run one query per title).
     */
    private function translate(array $tree): array
    {
        $lang = Session::get('lang');
        if ($lang === NULL) return $tree;

        $titles = [];
        array_walk_recursive($tree, function ($value, $key) use (&$titles) {
            if ($key === 'title' && is_string($value)) $titles[$value] = TRUE;
        });
        if (!$titles) return $tree;

        $translations = AppTranslation::where('lang', $lang)->whereIn('value', array_keys($titles))->pluck('translation', 'value')->all();

        $apply = function (array $nodes) use (&$apply, $translations) {
            foreach ($nodes as &$node) {
                if ($node['title'] !== NULL) $node['title'] = $translations[$node['title']] ?? $node['title'];
                $node['children'] = $apply($node['children']);
            }
            return $nodes;
        };
        return $apply($tree);
    }
}
