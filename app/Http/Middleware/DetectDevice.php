<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User\UserType;
use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class DetectDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //TODO: remove the s-admin restriction once the mobile layout is ready for all user types
        if (Auth::check() && UserType::init()->isUserSAdmin(Auth::user())) {
            Session::put('is_phone', Agent::isPhone());
        }

        return $next($request);
    }
}
