<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditTrailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // User before request (logout cases)
        $beforeUser = Auth::user() ?? Auth::guard('api')->user();

        $response = $next($request);

        // User after request (login cases)
        $afterUser = Auth::user() ?? Auth::guard('api')->user();

        $user = $afterUser ?? $beforeUser;

        if ($user && in_array($request->method(), ['POST','PUT','PATCH','DELETE'])) {

            $route = $request->route();
            $action = $route ? $route->getActionName() : null;

            // Skip broadcasting auth
            if ($action === '\Illuminate\Broadcasting\BroadcastController@authenticate') {
                return $response;
            }

            $payload = $request->except([
                'password',
                'password_confirmation',
                'token',
                'access_token'
            ]);

            DB::table('trails')->insert([
                'actioned_by_id' => $user->id,
                'actioned_by_name' => $user->name ?? null,
                'method' => $request->method(),
                'controller' => $action,
                'description' => $request->path(),
                'payload' => json_encode($payload),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return $response;
    }
}