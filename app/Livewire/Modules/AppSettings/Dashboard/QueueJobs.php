<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;
use Illuminate\Support\Carbon;

/**
 * Dashboard widget: queues, workers and failed jobs.
 *
 * TODO: real data from the jobs / failed_jobs tables (Queue::size(), queue:failed).
 */
class QueueJobs extends LivewireController
{
    public string $tileId;
    public array $settings = [];

    /** Failed job ids retried / forgotten in this session (dummy actions) */
    public array $handled = [];

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    public function retry(int $id): void
    {
        $this->handled[] = $id;
        $this->notifyMe(translator('Job pushed back to the queue'));
    }

    public function forget(int $id): void
    {
        $this->handled[] = $id;
        $this->notifyMe(translator('Failed job deleted'));
    }

    public function render()
    {
        $queue = $this->settings['queue'] ?? 'all';

        $queues = collect([
            ['name' => 'default',  'pending' => 4,   'workers' => 2, 'processed' => 1284, 'wait' => '2 s'],
            ['name' => 'mail',     'pending' => 0,   'workers' => 1, 'processed' => 312,  'wait' => '0 s'],
            ['name' => 'invoices', 'pending' => 37,  'workers' => 1, 'processed' => 96,   'wait' => '4 min'],
            ['name' => 'reports',  'pending' => 2,   'workers' => 0, 'processed' => 18,   'wait' => '—'],
        ]);
        if ($queue !== 'all') $queues = $queues->where('name', $queue)->values();

        $now = Carbon::now();
        $failed = collect([
            ['id' => 101, 'queue' => 'invoices', 'job' => 'App\Jobs\SendEInvoice',          'error' => 'ConnectionException: cURL error 28: Operation timed out', 'failed_at' => $now->copy()->subMinutes(18)],
            ['id' => 102, 'queue' => 'mail',     'job' => 'App\Mail\WorkdayDiaryReport',    'error' => 'Swift_TransportException: Expected response code 250 but got 421', 'failed_at' => $now->copy()->subHours(3)],
            ['id' => 103, 'queue' => 'reports',  'job' => 'App\Jobs\GenerateMonthlyHours',  'error' => 'Allowed memory size of 268435456 bytes exhausted', 'failed_at' => $now->copy()->subHours(7)],
            ['id' => 104, 'queue' => 'default',  'job' => 'App\Jobs\SyncCertificates',      'error' => 'ModelNotFoundException: No query results for model [Employee] 412', 'failed_at' => $now->copy()->subDay()],
        ])->reject(fn ($j) => in_array($j['id'], $this->handled));
        if ($queue !== 'all') $failed = $failed->where('queue', $queue)->values();

        return view('livewire.modules.app-settings.dashboard.queue-jobs', [
            'queues' => $queues,
            'failed' => $failed,
            'totals' => [
                'pending'   => $queues->sum('pending'),
                'workers'   => $queues->sum('workers'),
                'processed' => $queues->sum('processed'),
                'failed'    => $failed->count(),
            ],
        ]);
    }
}
