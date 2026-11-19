<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Store when the user signed in (users.last_login_at, shown in Admin - Users).
 * A plain query, so updated_at stays "last change of the user data".
 */
class RecordUserLogin
{
    public function handle(Login $event): void
    {
        DB::table('users')->where('id', $event->user->getAuthIdentifier())->update(['last_login_at' => now()]);
    }
}
