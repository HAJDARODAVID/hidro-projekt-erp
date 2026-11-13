<?php

namespace App\Services\Config;

/**
 * Remembers which dashboard layout a user has selected, per dashboard type.
 * Stored value: [type => app_dashboard.id]
 */
class ActiveDashboardLayoutConfigService extends BaseUserConfigService
{
    protected string $configKey = 'active_dashboard_layouts';

    public function getActiveId(string $type, ?int $userId = null): ?int
    {
        $value = $this->getValue($userId) ?? [];

        return isset($value[$type]) ? (int) $value[$type] : NULL;
    }

    public function setActiveId(string $type, int $dashboardId, ?int $userId = null): bool
    {
        $value = $this->getValue($userId) ?? [];
        $value[$type] = $dashboardId;

        return $this->setValue($value, $userId);
    }
}
