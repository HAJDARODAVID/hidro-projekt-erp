<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;
use Illuminate\Support\Carbon;

/**
 * Dashboard widget: who is using the app right now.
 *
 * TODO: real data from the sessions table / a last_activity column on users.
 */
class ActiveUsers extends LivewireController
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
        $window = (int) ($this->settings['window'] ?? 15);
        $now = Carbon::now();

        $users = collect([
            ['name' => 'Marko Horvat',  'role' => 'Site manager', 'page' => 'Workday diary',        'device' => 'phone',   'last' => $now->copy()->subSeconds(40)],
            ['name' => 'Ivana Kovač',   'role' => 'Accounting',   'page' => 'Bill overview',        'device' => 'desktop', 'last' => $now->copy()->subMinutes(2)],
            ['name' => 'Admin',         'role' => 'Super admin',  'page' => 'App control center',   'device' => 'desktop', 'last' => $now->copy()->subMinutes(3)],
            ['name' => 'Ante Babić',    'role' => 'Foreman',      'page' => 'Working hours',        'device' => 'phone',   'last' => $now->copy()->subMinutes(9)],
            ['name' => 'Petra Jurić',   'role' => 'HR',           'page' => 'Employees',            'device' => 'desktop', 'last' => $now->copy()->subMinutes(13)],
            ['name' => 'Luka Novak',    'role' => 'Warehouse',    'page' => 'Internal delivery note', 'device' => 'tablet', 'last' => $now->copy()->subMinutes(27)],
            ['name' => 'Maja Perić',    'role' => 'Procurement',  'page' => 'Suppliers',            'device' => 'desktop', 'last' => $now->copy()->subMinutes(48)],
        ])->filter(fn ($u) => $u['last']->greaterThanOrEqualTo($now->copy()->subMinutes($window)))->values();

        return view('livewire.modules.app-settings.dashboard.active-users', [
            'users'       => $users,
            'window'      => $window,
            'loginsToday' => 38,
            'failedLogin' => 3,
            'devices'     => $users->countBy('device'),
        ]);
    }
}
