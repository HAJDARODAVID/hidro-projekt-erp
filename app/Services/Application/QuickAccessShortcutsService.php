<?php

namespace App\Services\Application;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class QuickAccessShortcutsService
{
    /**
     * Returns known shortcuts available to frontend modal.
     */
    public function getShortcutsForClient(): array
    {
        return collect(config('quick-access-shortcuts.known_shortcuts', []))
            ->filter(fn(mixed $shortcut) => is_array($shortcut))
            ->map(fn(array $shortcut) => $this->hydrateShortcut($shortcut))
            ->filter(fn(?array $shortcut) => !is_null($shortcut))
            ->values()
            ->all();
    }

    /**
     * Execute action shortcut by code.
     *
     * @throws ValidationException
     */
    public function executeActionByCode(string $code): array
    {
        $shortcut = collect($this->getShortcutsForClient())
            ->first(fn(array $item) => ($item['code'] ?? null) === strtoupper(trim($code)));

        if (!$shortcut || ($shortcut['type'] ?? null) !== 'action') {
            throw ValidationException::withMessages([
                'code' => 'Unknown action shortcut code.',
            ]);
        }

        return match ($shortcut['action']) {
            'clear-cache' => $this->clearApplicationCache(),
            default => throw ValidationException::withMessages([
                'code' => 'Unsupported action shortcut.',
            ]),
        };
    }

    private function clearApplicationCache(): array
    {
        Artisan::call('cache:clear');
        Artisan::call('route:cache');
        Artisan::call('view:clear');
        Artisan::call('config:cache');

        return [
            'message' => 'Application cache has been cleared.',
        ];
    }

    private function hydrateShortcut(array $shortcut): ?array
    {
        if (($shortcut['type'] ?? null) !== 'route') {
            return $shortcut;
        }

        $resolvedRoute = $this->resolveRouteFromShortcut($shortcut);
        if (is_null($resolvedRoute)) {
            return null;
        }

        unset($shortcut['route_names']);
        $shortcut['url'] = $resolvedRoute;

        return $shortcut;
    }

    private function resolveRouteFromShortcut(array $shortcut): ?string
    {
        foreach (($shortcut['route_names'] ?? []) as $routeName) {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        }

        $singleRouteName = $shortcut['route_name'] ?? null;
        if (!is_null($singleRouteName) && Route::has($singleRouteName)) {
            return route($singleRouteName);
        }

        return null;
    }
}
