<?php

namespace App\Http\Controllers\HidroProjekt;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**Define the module name */
    protected $module = 'admin';

    public function moduleConfig()
    {
        $this->setMainTitle('Administration')
            ->setTabLinks();
    }

    /**
     * Users module: the app users (create, edit, activate / deactivate, password, roles).
     */
    public function users()
    {
        return $this->setMainTitle('Administration - Users')
            ->module('users');
    }
}
