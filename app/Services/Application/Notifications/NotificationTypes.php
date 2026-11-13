<?php

namespace App\Services\Application\Notifications;

/**
 * Registry of the notification types: label, icon, default level and who may see them.
 *
 * permission:
 *   NULL                      everybody (of the company)
 *   'some.acl.permission'     users holding that ACL permission (super admins always do)
 *   self::SUPER_ADMIN_ONLY    super admins only
 */
class NotificationTypes
{
    public const SYS_ERROR = 'sys_error';
    public const GENERAL = 'general';
    public const INT_ORDER = 'int_order';

    /** Pseudo permission nobody holds, except super admins (they pass every permission check) */
    public const SUPER_ADMIN_ONLY = 'super-admin-only';

    public const LEVELS = ['info', 'success', 'warning', 'error', 'critical'];

    /** Bootstrap color + icon per level */
    public const LEVEL_STYLE = [
        'info'     => ['color' => 'info',      'icon' => 'info-circle-fill'],
        'success'  => ['color' => 'success',   'icon' => 'check-circle-fill'],
        'warning'  => ['color' => 'warning',   'icon' => 'exclamation-circle-fill'],
        'error'    => ['color' => 'danger',    'icon' => 'exclamation-triangle-fill'],
        'critical' => ['color' => 'danger',    'icon' => 'x-octagon-fill'],
    ];

    public static function all(): array
    {
        return [
            self::SYS_ERROR => [
                'label'      => 'System error',
                'icon'       => 'bug',
                'level'      => 'error',
                'permission' => self::SUPER_ADMIN_ONLY,
            ],
            self::GENERAL => [
                'label'      => 'Notification',
                'icon'       => 'bell',
                'level'      => 'info',
                'permission' => NULL,
            ],
            self::INT_ORDER => [
                'label'      => 'Internal order',
                'icon'       => 'cart',
                'level'      => 'info',
                'permission' => NULL,
            ],
        ];
    }

    public static function get(string $type): array
    {
        return self::all()[$type] ?? throw new \InvalidArgumentException("Unknown notification type [$type]");
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    /**
     * Numeric weight of a level, for "minimum level" filters.
     */
    public static function levelWeight(string $level): int
    {
        return (int) array_search($level, self::LEVELS, TRUE);
    }

    /**
     * All levels at or above $minLevel.
     */
    public static function levelsFrom(string $minLevel): array
    {
        return array_values(array_filter(self::LEVELS, fn ($l) => self::levelWeight($l) >= self::levelWeight($minLevel)));
    }
}
