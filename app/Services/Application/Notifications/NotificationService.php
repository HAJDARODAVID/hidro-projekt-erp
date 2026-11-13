<?php

namespace App\Services\Application\Notifications;

use App\Models\Application\AppNotification;
use App\Models\Application\AppNotificationRead;
use App\Models\User;
use App\Services\Application\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Storing, grouping and handling notifications.
 *
 * Grouping (PendingNotification::groupBy): a notification with the same type + fingerprint + company
 * as an existing one does not create a new row. The existing one gets occurrences + 1, the new
 * last_seen_at / message / data, is reopened if it was resolved and becomes unread for everybody again.
 */
class NotificationService
{
    public static function store(PendingNotification $n): AppNotification
    {
        $now = now();
        $companyId = $n->resolveCompanyId();

        if ($n->fingerprint !== NULL) {
            $existing = CompanyContext::scope(
                self::newQuery($n->connection)->where('type', $n->type)->where('fingerprint', $n->fingerprint),
                $companyId
            )->latest('id')->first();

            if ($existing) {
                self::newQuery($n->connection)->whereKey($existing->id)->update([
                    'occurrences'  => DB::raw('occurrences + 1'),
                    'last_seen_at' => $now,
                    'level'        => $n->level,
                    'title'        => $n->title,
                    'message'      => $n->message,
                    'data'         => json_encode($n->data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                    'user_id'      => $n->causedByUserId,
                    'url'          => $n->url,
                    'resolved_at'  => NULL,
                    'resolved_by'  => NULL,
                    'updated_at'   => $now,
                ]);
                // A new occurrence is unread for everybody again
                AppNotificationRead::on($n->connection)->where('notification_id', $existing->id)->delete();

                return $existing->refresh();
            }
        }

        $notification = new AppNotification([
            'type'              => $n->type,
            'level'             => $n->level,
            'title'             => $n->title,
            'message'           => $n->message,
            'data'              => $n->data ?: NULL,
            'fingerprint'       => $n->fingerprint,
            'occurrences'       => 1,
            'first_seen_at'     => $now,
            'last_seen_at'      => $now,
            'user_id'           => $n->causedByUserId,
            'recipient_user_id' => $n->recipientUserId,
            'permission'        => $n->recipientUserId ? NULL : $n->permission,
            'company_id'        => $companyId,
            'url'               => $n->url,
        ]);
        if ($n->connection) $notification->setConnection($n->connection);
        $notification->save();

        return $notification;
    }

    /**
     * Notifications the user may see.
     */
    public static function visibleTo(User $user): Builder
    {
        return AppNotification::query()->visibleTo($user);
    }

    public static function unreadCount(User $user, string ...$types): int
    {
        return self::visibleTo($user)
            ->when($types, fn ($q) => $q->ofType(...$types))
            ->open()
            ->unreadBy($user)
            ->count();
    }

    /**
     * Find a notification the user may see (NULL if not visible).
     */
    public static function findFor(User $user, int $id): ?AppNotification
    {
        return self::visibleTo($user)->find($id);
    }

    public static function markRead(AppNotification $notification, User $user): void
    {
        AppNotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public static function markUnread(AppNotification $notification, User $user): void
    {
        AppNotificationRead::where('notification_id', $notification->id)->where('user_id', $user->id)->delete();
    }

    /**
     * Mark everything visible (optionally of some types) as read.
     *
     * @return int number of notifications marked
     */
    public static function markAllRead(User $user, string ...$types): int
    {
        $ids = self::visibleTo($user)
            ->when($types, fn ($q) => $q->ofType(...$types))
            ->unreadBy($user)
            ->pluck('id');

        $now = now();
        AppNotificationRead::insertOrIgnore($ids->map(fn ($id) => [
            'notification_id' => $id,
            'user_id'         => $user->id,
            'read_at'         => $now,
        ])->all());

        return $ids->count();
    }

    /**
     * Resolve for everybody (e.g. an error that has been fixed).
     */
    public static function resolve(AppNotification $notification, User $user): void
    {
        $notification->forceFill(['resolved_at' => now(), 'resolved_by' => $user->id])->save();
    }

    public static function reopen(AppNotification $notification): void
    {
        $notification->forceFill(['resolved_at' => NULL, 'resolved_by' => NULL])->save();
    }

    private static function newQuery(?string $connection): Builder
    {
        return $connection ? AppNotification::on($connection) : AppNotification::query();
    }
}
