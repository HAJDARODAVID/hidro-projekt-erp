<?php

namespace App\Services\Application\Notifications\Contracts;

/**
 * An exception that decides its own sys_error level (info, success, warning, error, critical)
 * instead of the ErrorNotificationReporter guessing it from the class.
 */
interface HasNotificationLevel
{
    public function notificationLevel(): string;
}
