<?php

namespace App\Exceptions;

use App\Services\Application\Notifications\Contracts\HasNotificationLevel;

/**
 * Something is wrong but not broken (unexpected data, fallback used, slow external service...).
 * Report it with report(new AppWarningException('...')) to get a "warning" sys_error without stopping the request.
 */
class AppWarningException extends \RuntimeException implements HasNotificationLevel
{
    public function notificationLevel(): string
    {
        return 'warning';
    }
}
