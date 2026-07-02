<?php

namespace App\Services\Application;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class QuickAccessShortcutsService
{
    /**
     * Returns all shortcuts available to frontend modal.
     */
    public function getShortcutsForClient(): array
    {
        $attendanceRoute = $this->resolveAttendanceRoute();
        $shortcuts = [];

        if (!is_null($attendanceRoute)) {
            $shortcuts[] = [
                'code' => 'ATT',
                'label' => 'Attendance module',
                'type' => 'route',
                'url' => $attendanceRoute,
            ];
        }

        $shortcuts[] = [
            'code' => 'CLR',
            'label' => 'Clear application cache',
            'type' => 'action',
            'action' => 'clear-cache',
        ];

        return $shortcuts;
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

    private function resolveAttendanceRoute(): ?string
    {
        if (Route::has('getAllEmployeeWorkingHours')) {
            return route('getAllEmployeeWorkingHours');
        }

        if (Route::has('hp_allWorkHours')) {
            return route('hp_allWorkHours');
        }

        return null;
    }
}
