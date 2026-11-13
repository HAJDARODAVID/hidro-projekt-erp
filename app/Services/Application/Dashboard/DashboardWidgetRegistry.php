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
    /** Allowed tile widths (out of a 12 column grid) */
    public const WIDTHS = [3 => '1/4', 4 => '1/3', 6 => '1/2', 8 => '2/3', 9 => '3/4', 12 => 'Full'];

    /** Allowed tile heights (in grid rows) */
    public const HEIGHTS = [2 => 'S', 3 => 'M', 4 => 'L', 6 => 'XL'];

    public static function all(): array
    {
        return [
            'app-errors' => [
                'title'       => 'App errors',
                'description' => 'Latest application errors and exceptions grouped by occurrence.',
                'icon'        => 'bug',
                'component'   => 'modules.app-settings.dashboard.app-errors',
                'w'           => 8,
                'h'           => 4,
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
