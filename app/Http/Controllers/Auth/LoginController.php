<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Only active users may sign in (deactivated in Admin - Users).
     */
    protected function credentials(Request $request)
    {
        return array_merge($request->only($this->username(), 'password'), ['active' => 1]);
    }

    /**
     * A deactivated user with the right password gets a clear message instead of "wrong credentials".
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where($this->username(), $request->input($this->username()))->first();
        if ($user && !$user->active && Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                $this->username() => [translator('Your user account is deactivated. Please contact the administrator.')],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
