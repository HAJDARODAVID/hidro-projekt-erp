<?php

namespace App\Services\Application\Dashboard;

use App\Models\Application\AppDashboard;
use App\Models\User;
use App\Services\Config\ActiveDashboardLayoutConfigService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Loading / saving of dashboard layouts (app_dashboard), independent of the dashboard type.
 *
 * Rules:
 *  - a user sees their own layouts + every global layout of their company (company_id, NULL = no company yet),
 *  - only the owner can change, rename, share or delete a layout,
 *  - the selected layout per dashboard type is remembered in the user config.
 */
class DashboardLayoutService
{
    public function __construct(
        private ActiveDashboardLayoutConfigService $activeConfig,
    ) {}

    /**
     * All layouts of a dashboard type the user can choose from (own first, then global ones of others).
     */
    public function available(string $type, int $userId): Collection
    {
        return AppDashboard::ofType($type)
            ->visibleTo($userId, $this->companyId($userId))
            ->with('user:id,name')
            ->orderByRaw('user_id = ? DESC', [$userId])
            ->orderBy('name')
            ->get();
    }

    /**
     * The layout the user works with. Falls back to their first own layout, then the first global one,
     * and creates a private layout from $defaultLayout when there is nothing to show.
     */
    public function resolveActive(string $type, int $userId, array $defaultLayout): AppDashboard
    {
        $activeId = $this->activeConfig->getActiveId($type, $userId);
        $dashboard = $activeId ? $this->findVisible($type, $activeId, $userId) : NULL;

        $dashboard ??= AppDashboard::ofType($type)->where('user_id', $userId)->orderBy('id')->first()
            ?? AppDashboard::ofType($type)->global($this->companyId($userId))->orderBy('id')->first()
            ?? $this->create($type, $userId, translator('My layout'), $defaultLayout);

        if ($dashboard->id !== $activeId) $this->activeConfig->setActiveId($type, $dashboard->id, $userId);

        return $dashboard;
    }

    public function findVisible(string $type, int $id, int $userId): ?AppDashboard
    {
        return AppDashboard::ofType($type)->visibleTo($userId, $this->companyId($userId))->find($id);
    }

    public function select(string $type, int $id, int $userId): ?AppDashboard
    {
        $dashboard = $this->findVisible($type, $id, $userId);
        if ($dashboard) $this->activeConfig->setActiveId($type, $dashboard->id, $userId);

        return $dashboard;
    }

    public function create(string $type, int $userId, string $name, array $layout, string $visibility = AppDashboard::VISIBILITY_PRIVATE): AppDashboard
    {
        $dashboard = AppDashboard::create([
            'type'       => $type,
            'user_id'    => $userId,
            'company_id' => $this->companyId($userId),
            'name'       => $name,
            'visibility' => $visibility,
            'layout'     => $layout,
        ]);
        $this->activeConfig->setActiveId($type, $dashboard->id, $userId);

        return $dashboard;
    }

    /**
     * Private copy of a (own or global) layout for the user, with fresh tile ids.
     */
    public function duplicate(AppDashboard $source, int $userId, ?string $name = NULL): AppDashboard
    {
        $layout = array_map(fn ($tile) => array_merge($tile, ['id' => uniqid('tile_', TRUE)]), $source->layout ?? []);

        return $this->create($source->type, $userId, $name ?? $source->name . ' (' . translator('copy') . ')', $layout);
    }

    /**
     * Update the owner's layout (name, visibility, layout).
     *
     * @throws AuthorizationException when the user is not the owner
     */
    public function update(AppDashboard $dashboard, int $userId, array $attributes): AppDashboard
    {
        $this->authorizeOwner($dashboard, $userId);

        $dashboard->fill(array_intersect_key($attributes, array_flip(['name', 'visibility', 'layout'])))->save();

        return $dashboard;
    }

    /**
     * @throws AuthorizationException when the user is not the owner
     */
    public function delete(AppDashboard $dashboard, int $userId): void
    {
        $this->authorizeOwner($dashboard, $userId);
        $dashboard->delete();
    }

    /** Company id per user, cached for the request */
    private array $companyIds = [];

    private function companyId(int $userId): ?int
    {
        if (!array_key_exists($userId, $this->companyIds)) {
            $user = Auth::id() === $userId ? Auth::user() : User::find($userId);
            $this->companyIds[$userId] = AppDashboard::companyIdOf($user);
        }

        return $this->companyIds[$userId];
    }

    private function authorizeOwner(AppDashboard $dashboard, int $userId): void
    {
        if (!$dashboard->isOwnedBy($userId)) {
            throw new AuthorizationException(translator('Only the owner can change this layout.'));
        }
    }
}
