<?php

namespace App\Services\Application\Dashboard;

/**
 * Registry of all widgets (modules) that can be placed on the app control center dashboard.
 *
 * To add a new widget:
 *  1. Create a Livewire component in App\Livewire\Modules\AppSettings\Dashboard (it receives $tileId and $settings).
 *  2. Register it here with its Livewire alias, default size and (optional) settings schema.
 *
 * Settings schema: key => ['label' => string, 'type' => 'select'|'number'|'text', 'options' => [value => label], 'default' => mixed]
 */
class DashboardWidgetRegistry
{
    /** Number of grid columns */
    public const COLUMNS = 24;

    /** Height of one grid row in px. Rows are small so a "Full" tile can end close to the screen bottom. */
    public const ROW_UNIT = 10;

    /** Vertical space under every tile in px (part of the tile's rows, there is no CSS row-gap) */
    public const TILE_GAP = 16;

    /** Allowed tile widths (out of COLUMNS) */
    public const WIDTHS = [
        4  => '1/6',
        6  => '1/4',
        8  => '1/3',
        10 => '5/12',
        12 => '1/2',
        14 => '7/12',
        16 => '2/3',
        18 => '3/4',
        20 => '5/6',
        24 => 'Full',
    ];

    /** Height value meaning "stretch to the bottom of the screen" (calculated in the browser) */
    public const HEIGHT_FULL = 0;

    /** Allowed tile heights (in ROW_UNIT rows, the tile gap included, or HEIGHT_FULL) */
    public const HEIGHTS = [
        20 => 'XS',
        30 => 'S',
        42 => 'M',
        52 => 'L',
        64 => 'XL',
        self::HEIGHT_FULL => 'Full',
    ];

    /** Rows used for a "Full" tile before the browser calculates its real height, and its minimum */
    public const FULL_FALLBACK_ROWS = 64;
    public const FULL_MIN_ROWS = 42;

    public static function all(): array
    {
        return [
            'app-errors' => [
                'title'       => 'App errors',
                'description' => 'Latest application errors and exceptions grouped by occurrence.',
                'icon'        => 'bug',
                'component'   => 'modules.app-settings.dashboard.app-errors',
                'w'           => 16,
                'h'           => 52,
                'settings'    => [
                    'level' => [
                        'label'   => 'Minimum level',
                        'type'    => 'select',
                        'options' => ['warning' => 'Warning', 'error' => 'Error', 'critical' => 'Critical'],
                        'default' => 'warning',
                    ],
                    'period' => [
                        'label'   => 'Period',
                        'type'    => 'select',
                        'options' => ['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days'],
                        'default' => '24h',
                    ],
                    'limit' => [
                        'label'   => 'Max rows',
                        'type'    => 'number',
                        'default' => 10,
                    ],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? NULL;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * Default settings values for a widget.
     */
    public static function defaultSettings(string $key): array
    {
        $widget = self::get($key);
        if (!$widget) return [];

        return collect($widget['settings'] ?? [])->map(fn ($s) => $s['default'] ?? NULL)->all();
    }

    /**
     * The layout a user gets when nothing is saved yet.
     */
    public static function defaultLayout(): array
    {
        return [
            self::makeTile('app-errors'),
        ];
    }

    public static function makeTile(string $key): array
    {
        $widget = self::get($key);

        return [
            'id'       => uniqid('tile_'),
            'widget'   => $key,
            'title'    => $widget['title'],
            'w'        => $widget['w'],
            'h'        => $widget['h'],
            'settings' => self::defaultSettings($key),
        ];
    }
}
