<?php

namespace App\Livewire\Modules\AppSettings\Dashboard;

use App\Livewire\LivewireController;
use App\Models\Application\AppNotification;
use App\Services\Application\Notifications\NotificationService;
use App\Services\Application\Notifications\NotificationTypes;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * Dashboard widget: application errors (sys_error notifications, grouped by fingerprint).
 */
class AppErrors extends LivewireController
{
    /** Levels shown by this widget (a "minimum level" setting picks from these) */
    public const LEVELS = ['warning', 'error', 'critical'];

    public string $tileId;
    public array $settings = [];

    /** In-widget quick filter (NULL = all levels allowed by the settings) */
    public ?string $levelFilter = NULL;

    /** Expanded error (shows details + trace) */
    public ?int $expanded = NULL;

    public bool $showResolved = FALSE;

    /** Error shown in the details modal */
    public ?int $detailId = NULL;

    public function mount(string $tileId, array $settings = [])
    {
        $this->tileId = $tileId;
        $this->settings = $settings;
    }

    /**
     * Re-render when something reported new errors (e.g. the DevTool widget).
     */
    #[On('app-errors-refresh')]
    public function refreshList(): void
    {
    }

    public function setLevelFilter(?string $level = NULL): void
    {
        $this->levelFilter = $this->levelFilter === $level || !in_array($level, self::LEVELS, TRUE) ? NULL : $level;
        $this->expanded = NULL;
    }

    public function toggleShowResolved(): void
    {
        $this->showResolved = !$this->showResolved;
        $this->expanded = NULL;
    }

    public function toggleExpanded(int $id): void
    {
        if ($this->expanded === $id) {
            $this->expanded = NULL;
            return;
        }

        $notification = $this->find($id);
        if (!$notification) return;

        $this->expanded = $id;
        NotificationService::markRead($notification, Auth::user());
    }

    public function openDetails(int $id): void
    {
        $notification = $this->find($id);
        if (!$notification) return;

        $this->detailId = $id;
        NotificationService::markRead($notification, Auth::user());
        $this->openModal();
    }

    public function beforeCloseModal(): void
    {
        $this->detailId = NULL;
    }

    public function resolve(int $id): void
    {
        $notification = $this->find($id);
        if (!$notification) return;

        NotificationService::resolve($notification, Auth::user());
        if ($this->expanded === $id && !$this->showResolved) $this->expanded = NULL;
        $this->notifyMe(translator('Error marked as resolved'));
    }

    public function reopen(int $id): void
    {
        $notification = $this->find($id);
        if (!$notification) return;

        NotificationService::reopen($notification);
        $this->notifyMe(translator('Error reopened'));
    }

    public function markAllRead(): void
    {
        $count = NotificationService::markAllRead(Auth::user(), NotificationTypes::SYS_ERROR);
        $this->notifyMe($count . ' ' . translator('errors marked as read'));
    }

    /**
     * Only errors the user may see (super admins) can be handled.
     */
    private function find(int $id): ?AppNotification
    {
        $notification = NotificationService::findFor(Auth::user(), $id);

        return $notification && $notification->type === NotificationTypes::SYS_ERROR ? $notification : NULL;
    }

    public function render()
    {
        $user = Auth::user();
        $minLevel = in_array($this->settings['level'] ?? NULL, self::LEVELS, TRUE) ? $this->settings['level'] : 'warning';
        $since = match ($this->settings['period'] ?? '24h') {
            '7d'    => now()->subDays(7),
            '30d'   => now()->subDays(30),
            'all'   => NULL,
            default => now()->subDay(),
        };

        $base = NotificationService::visibleTo($user)
            ->ofType(NotificationTypes::SYS_ERROR)
            ->minLevel($minLevel)
            ->when($since, fn ($q) => $q->seenSince($since));

        // Occurrences per level of the open errors
        $countsByLevel = (clone $base)->open()
            ->selectRaw('level, SUM(occurrences) as total')
            ->groupBy('level')
            ->pluck('total', 'level');
        $counts = collect(NotificationTypes::levelsFrom($minLevel))
            ->filter(fn ($l) => in_array($l, self::LEVELS, TRUE))
            ->mapWithKeys(fn ($l) => [$l => (int) ($countsByLevel[$l] ?? 0)]);

        $list = (clone $base)
            ->when(!$this->showResolved, fn ($q) => $q->open())
            ->when($this->levelFilter, fn ($q) => $q->where('level', $this->levelFilter));

        return view('livewire.modules.app-settings.dashboard.app-errors', [
            'items'         => (clone $list)
                ->with(['user:id,name', 'resolver:id,name', 'reads' => fn ($r) => $r->where('user_id', $user->id)])
                ->orderByRaw('resolved_at IS NOT NULL')
                ->orderByDesc('last_seen_at')
                ->limit(max(1, min(100, (int) ($this->settings['limit'] ?? 10))))
                ->get(),
            'total'         => (clone $list)->count(),
            'unread'        => (clone $base)->open()->unreadBy($user)->count(),
            'resolvedCount' => (clone $base)->resolved()->count(),
            'counts'        => $counts,
            'detail'        => $this->detailId ? $this->find($this->detailId)?->load('user:id,name', 'resolver:id,name') : NULL,
        ]);
    }
}
