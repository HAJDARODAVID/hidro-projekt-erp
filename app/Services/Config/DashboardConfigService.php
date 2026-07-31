<?php

namespace App\Services\Config;

class DashboardConfigService extends BaseUserConfigService
{
    protected string $configKey = 'dashboard_layout';

    /**
     * Get the ordered list of enabled dashboard widgets for a user.
     *
     * @param  int|null  $userId
     * @return array
     */
    public function getUserWidgets(?int $userId = null): array
    {
        $availableWidgets = config('dashboard-widgets.available_widgets');
        $layout = $this->getValue($userId) ?? config('dashboard-widgets.default_layout');

        $widgets = [];
        foreach ($layout as $widgetKey) {
            if (isset($availableWidgets[$widgetKey])) {
                $widgets[] = array_merge(['key' => $widgetKey], $availableWidgets[$widgetKey]);
            }
        }

        return $widgets;
    }

    /**
     * Persist the user's chosen widget order/selection.
     *
     * @param  array  $widgetKeys
     * @param  int|null  $userId
     * @return bool
     */
    public function saveUserWidgets(array $widgetKeys, ?int $userId = null): bool
    {
        $widgetKeys = array_values(array_intersect($widgetKeys, array_keys(config('dashboard-widgets.available_widgets'))));

        return $this->setValue($widgetKeys, $userId);
    }
}
