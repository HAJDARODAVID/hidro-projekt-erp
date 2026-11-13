<?php

namespace App\Livewire\Modules\AppSettings;

use App\Livewire\LivewireController;
use App\Models\Application\AppDashboard;
use App\Services\Application\Dashboard\DashboardLayoutService;
use App\Services\Application\Dashboard\DashboardWidgetRegistry as Registry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * App control center -> Dashboard.
 * Tile based, configurable dashboard (layout + widgets).
 * Layouts are stored in app_dashboard (type = app-settings): a user has own layouts and can use the global ones.
 */
class Index extends LivewireController
{
    protected const DASHBOARD_TYPE = AppDashboard::TYPE_APP_SETTINGS;

    /** The selected layout (app_dashboard.id) */
    #[Locked]
    public ?int $dashboardId = NULL;

    /** Only the owner can change the layout; others have to make a copy first */
    #[Locked]
    public bool $isOwner = FALSE;

    public string $layoutName = '';
    public string $visibility = AppDashboard::VISIBILITY_PRIVATE;

    /** The tiles on the dashboard, in display order */
    public array $layout = [];

    /** Edit mode shows the tile controls (move, resize, configure, remove) */
    public bool $editMode = FALSE;

    /** Show the "add widget" panel */
    public bool $showCatalog = FALSE;

    /** Tile currently being configured + its form data */
    public ?string $configTileId = NULL;
    public array $configForm = [];

    public function mount()
    {
        $this->loadDashboard($this->layouts()->resolveActive(static::DASHBOARD_TYPE, Auth::id(), Registry::defaultLayout()));
    }

    /* ---------------------------------------------------------------- layouts (app_dashboard) */

    public function selectLayout(int $id): void
    {
        $dashboard = $this->layouts()->select(static::DASHBOARD_TYPE, $id, Auth::id());
        if (!$dashboard) return;

        $this->loadDashboard($dashboard);
        $this->editMode = FALSE;
    }

    public function createLayout(): void
    {
        $count = AppDashboard::ofType(static::DASHBOARD_TYPE)->where('user_id', Auth::id())->count();
        $this->loadDashboard($this->layouts()->create(static::DASHBOARD_TYPE, Auth::id(), translator('Layout') . ' ' . ($count + 1), Registry::defaultLayout()));
        $this->editMode = TRUE;
        $this->notifyMe(translator('New layout created'));
    }

    public function duplicateLayout(): void
    {
        $source = $this->currentDashboard();
        if (!$source) return;

        $this->loadDashboard($this->layouts()->duplicate($source, Auth::id()));
        $this->notifyMe(translator('Layout copied'));
    }

    /**
     * Rename (wire:model.blur on the name input).
     */
    public function updatedLayoutName(): void
    {
        $name = mb_substr(trim($this->layoutName), 0, 100);
        $dashboard = $this->currentDashboard();
        if (!$dashboard || !$this->isOwner || $name === '') {
            $this->layoutName = $dashboard?->name ?? '';
            return;
        }

        $this->layoutName = $name;
        $this->layouts()->update($dashboard, Auth::id(), ['name' => $name]);
    }

    public function setVisibility(string $visibility): void
    {
        if (!in_array($visibility, [AppDashboard::VISIBILITY_PRIVATE, AppDashboard::VISIBILITY_GLOBAL])) return;
        $dashboard = $this->currentDashboard();
        if (!$dashboard || !$this->isOwner) return;

        $this->layouts()->update($dashboard, Auth::id(), ['visibility' => $visibility]);
        $this->visibility = $visibility;
        $this->notifyMe($visibility === AppDashboard::VISIBILITY_GLOBAL
            ? translator('Layout is now visible to everybody')
            : translator('Layout is now private'));
    }

    public function deleteLayout(): void
    {
        $dashboard = $this->currentDashboard();
        if (!$dashboard || !$this->isOwner) return;

        $this->layouts()->delete($dashboard, Auth::id());
        $this->editMode = FALSE;
        $this->closeConfig();
        $this->loadDashboard($this->layouts()->resolveActive(static::DASHBOARD_TYPE, Auth::id(), Registry::defaultLayout()));
        $this->notifyMe(translator('Layout deleted'));
    }

    /* ---------------------------------------------------------------- edit mode */

    public function toggleEditMode(): void
    {
        // Somebody else's (global) layout: customize a private copy of it
        if (!$this->editMode && !$this->isOwner) {
            $this->duplicateLayout();
            if (!$this->isOwner) return;
        }

        $this->editMode = !$this->editMode;
        $this->showCatalog = FALSE;
        $this->closeConfig();
    }

    public function toggleCatalog(): void
    {
        $this->showCatalog = !$this->showCatalog;
    }

    public function addWidget(string $key): void
    {
        if (!Registry::exists($key)) return;

        $this->layout[] = Registry::makeTile($key);
        $this->showCatalog = FALSE;
        $this->persist();
    }

    public function removeTile(string $tileId): void
    {
        $this->layout = array_values(array_filter($this->layout, fn ($t) => $t['id'] !== $tileId));
        if ($this->configTileId === $tileId) $this->closeConfig();
        $this->persist();
    }

    public function resizeTile(string $tileId, string $dimension, int $value): void
    {
        $allowed = $dimension === 'w' ? Registry::WIDTHS : ($dimension === 'h' ? Registry::HEIGHTS : []);
        if (!array_key_exists($value, $allowed)) return;

        $this->updateTile($tileId, fn ($t) => array_merge($t, [$dimension => $value]));
    }

    /**
     * Move a tile one step in the order (direction -1 / +1).
     */
    public function moveTile(string $tileId, int $direction): void
    {
        $index = $this->tileIndex($tileId);
        $target = $index + ($direction < 0 ? -1 : 1);
        if ($index === NULL || !isset($this->layout[$target])) return;

        [$this->layout[$index], $this->layout[$target]] = [$this->layout[$target], $this->layout[$index]];
        $this->persist();
    }

    /**
     * Drag & drop: place the dragged tile at the position of the target tile.
     */
    public function dropTile(string $draggedId, string $targetId): void
    {
        $from = $this->tileIndex($draggedId);
        $to = $this->tileIndex($targetId);
        if ($from === NULL || $to === NULL || $from === $to) return;

        $tile = array_splice($this->layout, $from, 1)[0];
        array_splice($this->layout, $to, 0, [$tile]);
        $this->persist();
    }

    public function resetLayout(): void
    {
        $this->layout = Registry::defaultLayout();
        $this->closeConfig();
        $this->persist();
        $this->notifyMe(translator('Dashboard layout reset'));
    }

    /* ---------------------------------------------------------------- tile settings */

    public function openConfig(string $tileId): void
    {
        $index = $this->tileIndex($tileId);
        if ($index === NULL) return;

        $tile = $this->layout[$index];
        $this->configTileId = $tileId;
        $this->configForm = array_merge(['title' => $tile['title']], $tile['settings']);
    }

    public function closeConfig(): void
    {
        $this->configTileId = NULL;
        $this->configForm = [];
    }

    public function saveConfig(): void
    {
        if (!$this->configTileId) return;

        $this->updateTile($this->configTileId, function ($tile) {
            $schema = Registry::get($tile['widget'])['settings'] ?? [];
            $settings = [];
            foreach ($schema as $key => $def) {
                $value = $this->configForm[$key] ?? $def['default'] ?? NULL;
                if ($def['type'] === 'select' && !array_key_exists($value, $def['options'])) $value = $def['default'];
                if ($def['type'] === 'number') $value = max(1, min(100, (int) $value));
                $settings[$key] = $value;
            }
            $tile['title'] = trim($this->configForm['title'] ?? '') ?: Registry::get($tile['widget'])['title'];
            $tile['settings'] = $settings;
            return $tile;
        });

        $this->closeConfig();
    }

    /* ---------------------------------------------------------------- helpers */

    private function tileIndex(string $tileId): ?int
    {
        foreach ($this->layout as $i => $tile) {
            if ($tile['id'] === $tileId) return $i;
        }
        return NULL;
    }

    private function updateTile(string $tileId, callable $callback): void
    {
        $index = $this->tileIndex($tileId);
        if ($index === NULL) return;

        $this->layout[$index] = $callback($this->layout[$index]);
        $this->persist();
    }

    /**
     * Drop tiles of widgets that no longer exist and fill in missing keys.
     */
    private function sanitizeLayout(array $layout): array
    {
        return array_values(array_filter(array_map(function ($tile) {
            if (!is_array($tile) || !Registry::exists($tile['widget'] ?? '')) return NULL;
            $default = Registry::makeTile($tile['widget']);
            // Only known keys / settings survive
            $tile = array_merge($default, array_intersect_key($tile, $default));
            $tile['settings'] = array_merge($default['settings'], array_intersect_key((array) $tile['settings'], $default['settings']));
            if (!is_string($tile['id']) || $tile['id'] === '') $tile['id'] = $default['id'];
            $tile['title'] = mb_substr(is_string($tile['title']) ? $tile['title'] : $default['title'], 0, 100);
            if (!array_key_exists($tile['w'], Registry::WIDTHS)) $tile['w'] = $default['w'];
            if (!array_key_exists($tile['h'], Registry::HEIGHTS)) $tile['h'] = $default['h'];
            return $tile;
        }, $layout)));
    }

    private function layouts(): DashboardLayoutService
    {
        return app(DashboardLayoutService::class);
    }

    private function currentDashboard(): ?AppDashboard
    {
        return $this->dashboardId ? $this->layouts()->findVisible(static::DASHBOARD_TYPE, $this->dashboardId, Auth::id()) : NULL;
    }

    private function loadDashboard(AppDashboard $dashboard): void
    {
        $this->dashboardId = $dashboard->id;
        $this->isOwner = $dashboard->isOwnedBy(Auth::id());
        $this->layoutName = $dashboard->name;
        $this->visibility = $dashboard->visibility;
        $this->layout = $this->sanitizeLayout($dashboard->layout ?? []);
        $this->closeConfig();
        $this->showCatalog = FALSE;
    }

    /**
     * Save the tiles to the selected layout. Only the owner can save; for anybody else the change is undone.
     */
    private function persist(): void
    {
        $dashboard = $this->currentDashboard();
        if (!$dashboard) return;

        if (!$this->isOwner) {
            $this->loadDashboard($dashboard);
            $this->showException(translator('Only the owner can change this layout.'));
            return;
        }

        // Sanitized again: $layout is a public property and could have been changed from the client
        $this->layout = $this->sanitizeLayout($this->layout);
        $this->layouts()->update($dashboard, Auth::id(), ['layout' => $this->layout]);
    }

    public function render()
    {
        $available = $this->layouts()->available(static::DASHBOARD_TYPE, Auth::id());

        return view('livewire.modules.app-settings.index', [
            'myLayouts'     => $available->filter(fn ($d) => $d->isOwnedBy(Auth::id())),
            'sharedLayouts' => $available->reject(fn ($d) => $d->isOwnedBy(Auth::id())),
            'catalog'    => Registry::all(),
            'widths'     => Registry::WIDTHS,
            'heights'    => Registry::HEIGHTS,
            'configTile' => $this->configTileId ? ($this->layout[$this->tileIndex($this->configTileId)] ?? NULL) : NULL,
        ]);
    }
}
