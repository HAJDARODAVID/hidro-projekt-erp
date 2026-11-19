<?php

namespace App\Services\HidroProjekt\ADM;

use App\Models\User;

/**
 * Class UserService.
 */
class UserService
{
    public static function doesWorkerHaveUser($wrkId){
        $user = User::where('worker_id', $wrkId)->first();
        return is_null($user);
    }

    public function createNewUser($data){
        User::create($data);
        return redirect()->route('getUsersModule');
    }
}
