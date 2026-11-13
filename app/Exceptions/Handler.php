<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Services\HidroProjekt\Domain\Notifications\NotificationsService;
use App\Services\Application\Notifications\Notify;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Old system (notifications table, old home dashboard card). Can fail (e.g. user without worker / cooperator),
            // so it is isolated from the new reporter.
            try {
                (new NotificationsService)->createNewSysErrorNotification($e);
            } catch (Throwable) {
            }

            // New system (app_notification): grouped, with trace + context, never throws
            Notify::exception($e);
        });
    }
}
