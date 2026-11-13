<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Exceptions\AppWarningException;
use App\Livewire\LivewireController;
use App\Services\Application\Acl\AclService;
use App\Services\Application\Notifications\Notify;
use App\Services\Application\Notifications\NotificationTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dashboard widget: developer tools to test the notification / error reporting.
 * Super admins only.
 */
class DevTool extends LivewireController
{
    public const MODES = ['report', 'throw'];
    public const REPEATS = [1, 5, 20];

    public string $tileId;
    public array $settings = [];

    /** report = caught + report() (request keeps working), throw = real uncaught exception */
    public string $mode = 'report';

    /** How many times the error is reported (report mode only) */
    public int $repeat = 1;

    /** New group every time (random token in the message) instead of the same grouped error */
    public bool $unique = FALSE;

    /** Last actions, newest first */
    public array $log = [];

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    public function trigger(string $level): void
    {
        if (!$this->allowed() || !in_array($level, ['critical', 'error', 'warning'], TRUE)) return;
        $token = $this->unique ? Str::lower(Str::random(6)) : NULL;

        if ($this->mode === 'throw') {
            $this->addLog($level, 'throw', 1);
            $this->cause($level, $token);
        }

        $repeat = in_array($this->repeat, self::REPEATS, TRUE) ? $this->repeat : 1;
        for ($i = 0; $i < $repeat; $i++) {
            try {
                $this->cause($level, $token);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->addLog($level, 'report', $repeat);
        $this->dispatch('app-errors-refresh');
        $this->notifyMe(translator('Reported') . ': ' . $level . ' × ' . $repeat);
    }

    /**
     * A direct notification to yourself (type general), to test non-error notifications.
     */
    public function sendTestNotification(): void
    {
        if (!$this->allowed()) return;

        Notify::type(NotificationTypes::GENERAL)
            ->level('info')
            ->title('DevTool test')
            ->message('Test notification sent at ' . now()->format('H:i:s'))
            ->toUser(auth()->id())
            ->send();

        $this->addLog('info', 'notification', 1);
        $this->notifyMe(translator('Test notification sent to you'));
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    /**
     * Cause a real error of the level. Always throws.
     * One line per level: repeated calls produce the same fingerprint (= one grouped error).
     */
    private function cause(string $level, ?string $token): never
    {
        match ($level) {
            // QueryException -> critical
            'critical' => DB::select('select * from devtool_missing_table' . ($token ? "_$token" : '')),
            // Error "Call to a member function ... on null" -> error
            'error'    => (function () use ($token) { $object = NULL; $object->{'devToolMissingMethod' . ($token ? ucfirst($token) : '')}(); })(),
            // AppWarningException (HasNotificationLevel) -> warning
            'warning'  => throw new AppWarningException('DevTool: simulated warning, a fallback value was used for a missing setting' . ($token ? " [$token]" : '')),
        };

        throw new \LogicException('DevTool: unreachable');
    }

    private function allowed(): bool
    {
        if (AclService::current()->isSuperAdmin()) return TRUE;

        $this->showException(translator('The DevTool is for super admins only.'));
        return FALSE;
    }

    private function addLog(string $level, string $mode, int $count): void
    {
        array_unshift($this->log, ['time' => now()->format('H:i:s'), 'level' => $level, 'mode' => $mode, 'count' => $count, 'unique' => $this->unique]);
        $this->log = array_slice($this->log, 0, 8);
    }

    public function render()
    {
        return view('livewire.modules.app-settings.dashboard.dev-tool', [
            'isProduction' => app()->environment('production'),
        ]);
    }
}
