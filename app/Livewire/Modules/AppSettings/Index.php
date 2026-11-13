<?php

namespace App\Livewire\Modules\AppSettings;

use App\Livewire\LivewireController;
use App\Services\Application\Dashboard\DashboardWidgetRegistry as Registry;

/**
 * App control center -> Dashboard.
 * Tile based, configurable dashboard (layout + widgets).
 *
 * TODO: the layout is kept in the session for now; move it to the DB (per user) once the widgets are real.
 */
class Index extends LivewireController
{
    private const SESSION_KEY = 'app_settings_dashboard_layout';

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
        $this->layout = $this->sanitizeLayout(session(self::SESSION_KEY, Registry::defaultLayout()));
    }

    /* ---------------------------------------------------------------- edit mode */

    public function toggleEditMode(): void
    {
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
            $tile = array_merge($default, $tile);
            $tile['settings'] = array_merge($default['settings'], $tile['settings'] ?? []);
            return $tile;
        }, $layout)));
    }

    private function persist(): void
    {
        session([self::SESSION_KEY => $this->layout]);
    }

    public function render()
    {
        return view('livewire.modules.app-settings.index', [
            'catalog'    => Registry::all(),
            'widths'     => Registry::WIDTHS,
            'heights'    => Registry::HEIGHTS,
            'configTile' => $this->configTileId ? ($this->layout[$this->tileIndex($this->configTileId)] ?? NULL) : NULL,
        ]);
    }
}
