<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
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

        $user = $user->load('employeeInformation');

        if (!$user->employeeInformation || $user->employeeInformation->account_status !== 'active') {
            auth()->logout();

            return redirect('/login')
                ->withErrors([
                    'email' => 'Sorry, your account is currently inactive.'
                ])
                ->withInput($request->only('email'));
        }

        $token = $user->createToken('app-token')->plainTextToken;

        session([
            'auth_token' => $token,
            'name' => $user->name,
            'email' => $user->email,
            'session_id' => session()->getId()
        ]);

        // Handle Remember Me: Save email and encrypted password to cookies for 30 days if checked, else remove cookies
        if ($request->has('remember')) {
            Cookie::queue('remember_email', $request->email, 60 * 24 * 30); // 30 days
            Cookie::queue('remember_password', encrypt($request->password), 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('remember_email'));
            Cookie::queue(Cookie::forget('remember_password'));
        }
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
        // Log out the user
        auth()->logout();

        // Forget the session variables
        $request->session()->forget('auth_token');
        $request->session()->forget('name');
        $request->session()->forget('email');

        // Do NOT clear the remember me cookies here, so they persist for pre-filling the login form

        // Optionally flush the whole session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}