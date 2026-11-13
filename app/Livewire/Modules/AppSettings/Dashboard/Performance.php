<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;
use Illuminate\Support\Carbon;

/**
 * Dashboard widget: request volume, response times and the slowest endpoints.
 *
 * TODO: real data (request logging middleware / Telescope / Pulse).
 */
class Performance extends LivewireController
{
    public string $tileId;
    public array $settings = [];

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    public function render()
    {
        // Fixed seed: the dummy chart stays the same between renders
        mt_srand(42);
        $now = Carbon::now()->startOfHour();
        $hours = collect(range(23, 0))->map(function ($ago) use ($now) {
            $hour = $now->copy()->subHours($ago);
            $h = (int) $hour->format('G');
            $base = ($h >= 6 && $h <= 16) ? 520 : (($h >= 17 && $h <= 21) ? 180 : 25);
            return [
                'label'    => $hour->format('H:i'),
                'requests' => $base + mt_rand(0, (int) ($base * .4)),
                'avg_ms'   => mt_rand(140, 420),
            ];
        });
        mt_srand();

        $max = max(1, $hours->max('requests'));
        $slowLimit = (int) ($this->settings['limit'] ?? 5);

        $slowest = collect([
            ['method' => 'GET',  'uri' => '/hp/bills/overview',          'avg' => 2840, 'p95' => 4910, 'hits' => 212],
            ['method' => 'POST', 'uri' => '/livewire/update (WorkingHours)', 'avg' => 1960, 'p95' => 3320, 'hits' => 1844],
            ['method' => 'GET',  'uri' => '/employees/certificates',     'avg' => 1210, 'p95' => 2050, 'hits' => 97],
            ['method' => 'GET',  'uri' => '/hp/construction-sites',      'avg' => 880,  'p95' => 1430, 'hits' => 640],
            ['method' => 'GET',  'uri' => '/app-settings/acl',           'avg' => 760,  'p95' => 990,  'hits' => 31],
            ['method' => 'POST', 'uri' => '/hp/internal_delivery_note',  'avg' => 540,  'p95' => 870,  'hits' => 158],
        ])->take($slowLimit);

        return view('livewire.modules.app-settings.dashboard.performance', [
            'hours'   => $hours,
            'max'     => $max,
            'slowest' => $slowest,
            'stats'   => [
                'requests'   => $hours->sum('requests'),
                'avg_ms'     => (int) round($hours->avg('avg_ms')),
                'p95_ms'     => 1240,
                'error_rate' => 0.8,
            ],
        ]);
    }
}
