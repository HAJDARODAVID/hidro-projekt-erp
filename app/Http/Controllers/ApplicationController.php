<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**Define the module name */
    protected $module = 'app-settings';

    public function moduleConfig()
    {
        $this->setMainTitle('App control center')
            ->setTabLinks()
            ->middlewareAttributes('s-admin-access-only');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        return $this->module();
    }

    /**
     * Show all the application GET routes.
     */
    public function getAllApplicationModules()
    {
        return $this->module('modules');
    }

    /**
     * Show translations module.
     */
    public function getTranslatorModules()
    {
        return $this->module('translator');
    }

    /**
     * Show the access control (roles, permissions, user access) module.
     */
    public function getAclModule()
    {
        return $this->module('acl');
    }
}
