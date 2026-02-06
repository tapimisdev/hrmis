<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (
            app()->runningInConsole() ||
            $request->is('login', 'logout', 'password/*', 'api/*')
        ) {
            return $next($request);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        $isEmployee = $user->roles->contains(function ($role) {
            return str_starts_with($role->name, 'emp_');
        });
        
        if ($isEmployee && $request->is('admin/*')) {
            return redirect()->to('/employee/dashboard');
        }

        if (!$isEmployee && $request->is('employee/*')) {
            return redirect()->to('/admin/dashboard');
        }


        return $next($request);
    }
}
