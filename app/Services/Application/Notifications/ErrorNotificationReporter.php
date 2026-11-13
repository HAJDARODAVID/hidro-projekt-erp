<?php

namespace App\Services\Application\Notifications;

use App\Services\Application\Notifications\Contracts\HasNotificationLevel;
use Illuminate\Support\Facades\Auth;

/**
 * Turns a reported exception into a grouped sys_error notification.
 *
 *  - works for web, Livewire, queue and console (logged in or not),
 *  - never throws (a failing reporter must not break the exception handler),
 *  - writes over its own DB connection, so the row survives when the failing code's transaction is rolled back,
 *  - groups by exception class + file + line + the message without numbers / quoted values / ids.
 */
class ErrorNotificationReporter
{
    private const ISOLATED_CONNECTION = 'app_notification_isolated';
    private const MAX_TRACE_FRAMES = 25;

    /** Exceptions (or parents) that are reported as critical */
    private const CRITICAL = [
        \PDOException::class,
        \Illuminate\Database\QueryException::class,
        \Illuminate\Http\Client\ConnectionException::class,
        \Illuminate\Contracts\Filesystem\FileNotFoundException::class,
        \Symfony\Component\ErrorHandler\Error\FatalError::class,
        \RedisException::class,
    ];


    /** Guard: an error while reporting must not report itself again */
    private static bool $reporting = FALSE;

    public static function report(\Throwable $e): void
    {
        if (self::$reporting) return;
        self::$reporting = TRUE;

        try {
            $userId = self::safe(fn () => Auth::id());
            $userName = self::safe(fn () => Auth::user()?->name);

            Notify::type(NotificationTypes::SYS_ERROR)
                ->level(self::level($e))
                ->title(class_basename($e))
                ->message($e->getMessage() !== '' ? $e->getMessage() : get_class($e))
                ->data([
                    'exception' => get_class($e),
                    'code'      => $e->getCode(),
                    'file'      => self::relativePath($e->getFile()),
                    'line'      => $e->getLine(),
                    'trace'     => self::trace($e),
                    'previous'  => $e->getPrevious() ? get_class($e->getPrevious()) . ': ' . mb_substr($e->getPrevious()->getMessage(), 0, 1000) : NULL,
                    'context'   => self::context(),
                    'user'      => $userId ? ['id' => $userId, 'name' => $userName] : NULL,
                    'env'       => app()->environment(),
                ])
                ->causedBy($userId)
                ->groupBy(self::fingerprint($e))
                ->onConnection(self::isolatedConnection())
                ->send();
        } catch (\Throwable $reportError) {
            // Last resort: PHP error log (never the Laravel handler, that would loop)
            error_log('[ErrorNotificationReporter] ' . get_class($reportError) . ': ' . $reportError->getMessage()
                . ' | while reporting ' . get_class($e) . ': ' . $e->getMessage());
        } finally {
            self::$reporting = FALSE;
        }
    }

    /**
     * The exception's own level (HasNotificationLevel, e.g. AppWarningException), else critical for
     * infrastructure failures, else error.
     * (Things like TokenMismatch / HTTP exceptions never get here: Laravel does not report them.)
     */
    public static function level(\Throwable $e): string
    {
        if ($e instanceof HasNotificationLevel && in_array($e->notificationLevel(), NotificationTypes::LEVELS, TRUE)) {
            return $e->notificationLevel();
        }
        foreach (self::CRITICAL as $class) {
            if ($e instanceof $class) return 'critical';
        }
        return 'error';
    }

    /**
     * Same error = same class, place and message "shape" (numbers, quoted values, uuids... removed).
     */
    public static function fingerprint(\Throwable $e): string
    {
        $message = $e->getMessage();
        $message = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i', '{uuid}', $message);
        $message = preg_replace('/(["\'`]).*?\1/s', '{str}', $message);
        $message = preg_replace('/\d+(\.\d+)?/', '{n}', $message);
        // SQL errors: the bindings part differs every time
        $message = preg_replace('/\(Connection: .*$/s', '', $message);

        return hash('sha256', implode('|', [get_class($e), self::relativePath($e->getFile()), $e->getLine(), trim($message)]));
    }

    private static function trace(\Throwable $e): array
    {
        return collect($e->getTrace())
            ->take(self::MAX_TRACE_FRAMES)
            ->map(fn ($f) => [
                'file'     => isset($f['file']) ? self::relativePath($f['file']) : NULL,
                'line'     => $f['line'] ?? NULL,
                'function' => (isset($f['class']) ? $f['class'] . ($f['type'] ?? '::') : '') . ($f['function'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Where did it happen: HTTP request (incl. Livewire component / method) or console command.
     * Only the path is stored (no query string, no input) so no tokens / passwords end up in the table.
     */
    private static function context(): array
    {
        if (app()->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            return [
                'kind'    => 'console',
                'command' => mb_substr(implode(' ', array_slice($argv, 0, 3)), 0, 255),
            ];
        }

        $request = request();
        $context = [
            'kind'   => 'http',
            'method' => $request->method(),
            'path'   => '/' . ltrim($request->path(), '/'),
            'route'  => self::safe(fn () => $request->route()?->getName()),
        ];

        // Livewire 3 update request: which component + method
        $component = $request->input('components.0');
        if (is_array($component)) {
            $snapshot = json_decode($component['snapshot'] ?? '', TRUE);
            $context['livewire'] = [
                'component' => $snapshot['memo']['name'] ?? NULL,
                'page'      => $snapshot['memo']['path'] ?? NULL,
                'method'    => $component['calls'][0]['method'] ?? NULL,
            ];
        }

        return $context;
    }

    /**
     * A second connection with the same config as the default one: writes on it are not part of
     * a transaction on the default connection, so they are kept when that transaction rolls back.
     */
    private static function isolatedConnection(): string
    {
        $name = self::ISOLATED_CONNECTION;
        if (!config("database.connections.$name")) {
            config(["database.connections.$name" => config('database.connections.' . config('database.default'))]);
        }
        return $name;
    }

    private static function relativePath(string $path): string
    {
        $base = base_path() . DIRECTORY_SEPARATOR;
        $path = str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
        return str_replace('\\', '/', $path);
    }

    private static function safe(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return NULL;
        }
    }
}
