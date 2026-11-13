<?php

namespace App\Services\Application\Notifications;

/**
 * Entry point of the notification system.
 *
 *   Notify::type('int_order')->title('...')->message('...')->toUser($userId)->send();
 *   Notify::exception($e);   // what the exception handler does
 */
class Notify
{
    public static function type(string $type): PendingNotification
    {
        return new PendingNotification($type);
    }

    /**
     * Report an exception as a grouped sys_error. Never throws.
     */
    public static function exception(\Throwable $e): void
    {
        ErrorNotificationReporter::report($e);
    }
}
