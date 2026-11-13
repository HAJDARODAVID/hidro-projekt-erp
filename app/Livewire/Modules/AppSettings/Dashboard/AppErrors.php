<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;
use Illuminate\Support\Carbon;

/**
 * Dashboard widget: application errors.
 *
 * TODO: replace dummyErrors() with a real source (log parser / error table).
 */
class AppErrors extends LivewireController
{
    private const LEVEL_WEIGHT = ['warning' => 1, 'error' => 2, 'critical' => 3];

    public string $tileId;
    public array $settings = [];

    /** In-widget quick filter (NULL = all levels allowed by the settings) */
    public ?string $levelFilter = NULL;

    /** Expanded error row (shows the stack trace) */
    public ?int $expanded = NULL;

    /** Ids resolved in this session (dummy action) */
    public array $resolved = [];

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    public function setLevelFilter(?string $level = NULL): void
    {
        $this->levelFilter = $this->levelFilter === $level ? NULL : ($level ?: NULL);
        $this->expanded = NULL;
    }

    public function toggleExpanded(int $id): void
    {
        $this->expanded = $this->expanded === $id ? NULL : $id;
    }

    public function resolve(int $id): void
    {
        $this->resolved[] = $id;
        if ($this->expanded === $id) $this->expanded = NULL;
        $this->notifyMe(translator('Error marked as resolved'));
    }

    public function render()
    {
        $minWeight = self::LEVEL_WEIGHT[$this->settings['level'] ?? 'warning'] ?? 1;
        $since = match ($this->settings['period'] ?? '24h') {
            '7d'    => now()->subDays(7),
            '30d'   => now()->subDays(30),
            default => now()->subDay(),
        };

        $errors = collect($this->dummyErrors())
            ->reject(fn ($e) => in_array($e['id'], $this->resolved))
            ->filter(fn ($e) => self::LEVEL_WEIGHT[$e['level']] >= $minWeight)
            ->filter(fn ($e) => $e['last_seen']->greaterThanOrEqualTo($since))
            ->sortByDesc('last_seen');

        $counts = collect(self::LEVEL_WEIGHT)
            ->filter(fn ($w) => $w >= $minWeight)
            ->map(fn ($w, $level) => $errors->where('level', $level)->sum('count'));

        if ($this->levelFilter) $errors = $errors->where('level', $this->levelFilter);

        return view('livewire.modules.app-settings.dashboard.app-errors', [
            'errors' => $errors->take((int) ($this->settings['limit'] ?? 10))->values(),
            'total'  => $errors->count(),
            'counts' => $counts,
        ]);
    }

    /**
     * Dummy data until the real error source is connected.
     */
    private function dummyErrors(): array
    {
        $now = Carbon::now();

        return [
            [
                'id' => 1, 'level' => 'critical', 'count' => 3,
                'exception' => 'Illuminate\Database\QueryException',
                'message'   => 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock',
                'file'      => 'app/Services/WorkingHours/WorkingHoursService.php', 'line' => 214,
                'url'       => 'POST /livewire/update', 'user' => 'm.horvat',
                'last_seen' => $now->copy()->subMinutes(12),
            ],
            [
                'id' => 2, 'level' => 'error', 'count' => 17,
                'exception' => 'ErrorException',
                'message'   => 'Undefined array key "construction_site_id"',
                'file'      => 'app/Livewire/Modules/WorkdayDiary/CreateNewDiaryModal.php', 'line' => 88,
                'url'       => 'POST /livewire/update', 'user' => 'i.kovac',
                'last_seen' => $now->copy()->subMinutes(47),
            ],
            [
                'id' => 3, 'level' => 'warning', 'count' => 42,
                'exception' => 'Deprecated',
                'message'   => 'Passing null to parameter #1 ($string) of type string is deprecated',
                'file'      => 'app/Http/Controllers/Controller.php', 'line' => 129,
                'url'       => 'GET /app-settings/modules', 'user' => 'admin',
                'last_seen' => $now->copy()->subHours(2),
            ],
            [
                'id' => 4, 'level' => 'error', 'count' => 5,
                'exception' => 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
                'message'   => 'The route hp/bills/export/2026-10 could not be found.',
                'file'      => 'vendor/laravel/framework/src/Illuminate/Routing/AbstractRouteCollection.php', 'line' => 45,
                'url'       => 'GET /hp/bills/export/2026-10', 'user' => 'a.babic',
                'last_seen' => $now->copy()->subHours(5),
            ],
            [
                'id' => 5, 'level' => 'critical', 'count' => 1,
                'exception' => 'Illuminate\Http\Client\ConnectionException',
                'message'   => 'cURL error 28: Operation timed out after 30001 milliseconds (FINA e-račun)',
                'file'      => 'app/Services/Invoices/EInvoiceSender.php', 'line' => 61,
                'url'       => 'artisan queue:work', 'user' => 'system',
                'last_seen' => $now->copy()->subHours(9),
            ],
            [
                'id' => 6, 'level' => 'warning', 'count' => 8,
                'exception' => 'Illuminate\Validation\ValidationException',
                'message'   => 'The OIB field must be 11 digits.',
                'file'      => 'app/Livewire/Modules/Employees/CreateWorkerModal.php', 'line' => 132,
                'url'       => 'POST /livewire/update', 'user' => 'p.juric',
                'last_seen' => $now->copy()->subDays(2),
            ],
            [
                'id' => 7, 'level' => 'error', 'count' => 2,
                'exception' => 'TypeError',
                'message'   => 'Argument #1 ($date) must be of type Carbon\Carbon, null given',
                'file'      => 'app/Livewire/Modules/Employees/CertTracker.php', 'line' => 57,
                'url'       => 'GET /employees/certificates', 'user' => 'admin',
                'last_seen' => $now->copy()->subDays(4),
            ],
            [
                'id' => 8, 'level' => 'critical', 'count' => 1,
                'exception' => 'Illuminate\Contracts\Filesystem\FileNotFoundException',
                'message'   => 'File does not exist at path storage/app/acl/acl-sync.json',
                'file'      => 'app/Services/Application/Acl/AclSyncService.php', 'line' => 33,
                'url'       => 'POST /livewire/update', 'user' => 'admin',
                'last_seen' => $now->copy()->subDays(12),
            ],
        ];
    }
}
