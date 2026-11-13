<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;

/**
 * Dashboard widget: server / runtime health.
 * Environment info is real, the resource usage is dummy data.
 *
 * TODO: read real usage (sys_getloadavg, disk_free_space, DB status...).
 */
class ServerHealth extends LivewireController
{
    public string $tileId;
    public array $settings = [];

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    /**
     * Status of a usage value by the warning / critical thresholds from the settings.
     */
    private function status(float $percent): string
    {
        $warning = (int) ($this->settings['warning'] ?? 75);
        $critical = (int) ($this->settings['critical'] ?? 90);

        return $percent >= $critical ? 'critical' : ($percent >= $warning ? 'warning' : 'good');
    }

    public function render()
    {
        $meters = collect([
            ['label' => 'CPU load',       'icon' => 'cpu',          'value' => 37,  'detail' => '4 cores · load 1.48'],
            ['label' => 'Memory',         'icon' => 'memory',       'value' => 78,  'detail' => '12.5 / 16 GB'],
            ['label' => 'Disk',           'icon' => 'hdd',          'value' => 62,  'detail' => '310 / 500 GB'],
            ['label' => 'DB connections', 'icon' => 'database',     'value' => 22,  'detail' => '33 / 150'],
            ['label' => 'Storage logs',   'icon' => 'file-earmark-text', 'value' => 93, 'detail' => '1.86 / 2 GB (log rotation?)'],
        ])->map(fn ($m) => $m + ['status' => $this->status($m['value'])]);

        $info = [
            'Environment' => app()->environment(),
            'Debug mode'  => config('app.debug') ? 'ON' : 'off',
            'PHP'         => PHP_VERSION,
            'Laravel'     => app()->version(),
            'DB driver'   => config('database.default'),
            'Cache'       => config('cache.default'),
            'Queue'       => config('queue.default'),
            'Uptime'      => '23 d 4 h',
        ];

        return view('livewire.modules.app-settings.dashboard.server-health', [
            'meters' => $meters,
            'info'   => $info,
            'worst'  => $meters->contains('status', 'critical') ? 'critical' : ($meters->contains('status', 'warning') ? 'warning' : 'good'),
        ]);
    }
}
