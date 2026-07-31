<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkerModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkingDayRecordModel;
use App\Models\InventoryCheckingModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'emptyWorkingDay', 'checkIfUSerIsActive']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //Set the user language
        if (!Session::get('lang')) {
            Session::put('lang', Auth::user()->lang);
        }

        //Get the user update indicator
        $invokeUserUpdate = Auth::user()->inv_update;

        //Fill the session with user rights
        if (!Session::get('user_rights') || $invokeUserUpdate) {
            Session::put('user_rights', $this->getUserRights());
            if ($invokeUserUpdate) {
                //Set the user language
                Session::put('lang', Auth::user()->lang);
                Artisan::call('view:clear');
                User::where('id', Auth::user()->id)->first()->update([
                    'inv_update' => FALSE,
                ]);
            }
        }

        if (Auth::user()->type == User::USER_TYPE_GROUP_LEADER || Auth::user()->type == User::USER_TYPE_COOPERATOR) {
            return view('hidro-projekt.BDE.bdeIndex', [
                'myRecords' => WorkingDayRecordModel::where('user_id', Auth::user()->id)->where('date', date('Y-m-d'))->with('getConstructionSite')->get(),
                'activeInv' => InventoryCheckingModel::where('status', InventoryCheckingModel::INVENTORY_STATUS_ACTIVE)->first(),
            ]);
        } elseif (Session::get('is_phone')) {
            return view('dashboard', $this->getDashboardStats());
        } else {
            return view('hidro-projekt.admin');
        }
    }

    private function getDashboardStats()
    {
        return [
            'employedWorkersCount' => WorkerModel::where('status', WorkerModel::WORKER_STATUS_EMPLOYED)->count(),
        ];
    }

    private function getUserRights()
    {
        $user = Auth::user();
        $userRights = [];
        $userInfo = User::where('id', $user->id)
            ->with(
                'getSpecialPrivilege',
                'getSpecialPrivilege.getResources',
                'getUserRoles.getRoles.getRoleResources.getResource'
            )
            ->first();
        $specialPrivileges = $userInfo->getSpecialPrivilege;
        $roles =  $userInfo->getUserRoles;
        //Get special privileges
        foreach ($specialPrivileges as $resource) {
            $userRights[] = $resource->getResources->first()->resources;
        }
        //dd($roles);
        //Get roles privileges
        foreach ($roles as $role) {
            foreach ($role->getRoles->getRoleResources as $resource) {
                $userRights[] = $resource->getResource->resources;
            }
        }
        //remove duplicates
        $userRights = array_unique($userRights);
        return $userRights;
    }
}
