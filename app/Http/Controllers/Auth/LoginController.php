<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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
    protected function redirectTo()
    {
        $user = auth()->user();

        $hasEmployeeRole = $user->roles->contains(function ($role) {
            return str_starts_with($role->name, 'emp_');
        });

        if ($hasEmployeeRole) {
            return '/employee/dashboard';
        }

        return '/admin/dashboard';
    }

    protected function authenticated(Request $request, $user)
    {
        $token = $user->createToken('app-token')->plainTextToken;

        session([
            'auth_token' => $token,
            'name' => $user->name,
            'email' => $user->email,
            'session_id' => session()->getId()
        ]);
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function logout(Request $request)
    {
        // Forget the session variable
        $request->session()->forget('auth_token');
        $request->session()->forget('name');
        $request->session()->forget('email');

        // Optionally flush the whole session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
