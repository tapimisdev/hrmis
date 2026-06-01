<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

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
        return $this->dashboardPath(auth()->user());
    }

    protected function authenticated(Request $request, $user)
    {
        $user = $user->load('employeeInformation');
        $roles = $user->getRoleNames();

        $isEmployee = $roles->contains(function ($role) {
            return str_starts_with($role, 'emp');
        });

        if($isEmployee) {
            if (!$user->employeeInformation || $user->employeeInformation->account_status !== 'active') {
                auth()->logout();

                return redirect('/login')
                    ->withErrors([
                        'email' => 'Sorry, your account is currently inactive.'
                    ])
                    ->withInput($request->only('email'));
            }
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

        return redirect()->intended($this->dashboardPath($user));
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

    protected function credentials(Request $request)
    {
        $login = trim($request->input($this->username()));

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $login,
                'password' => $request->input('password'),
            ];
        }

        return [
            'id' => DB::table('employee_information')
                ->where('employee_no', $login)
                ->value('user_id'),
            'password' => $request->input('password'),
        ];
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

    protected function dashboardPath($user): string
    {
        $roles = $user?->getRoleNames() ?? collect();

        $isEmployee = $roles->contains(function ($role) {
            return str_starts_with($role, 'emp');
        });

        return $isEmployee ? '/employee/dashboard' : '/admin/dashboard';
    }
}
