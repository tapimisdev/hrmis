<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhitelistBiometricIP
{
    // allowed IPs
    protected $whitelisted = [
        '192.168.1.217', // UFace 402
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        if (!in_array($ip, $this->whitelisted)) {
            return response()->json([
                'message' => 'Unauthorized IP',
                'ip' => $ip
            ], 403);
        }

        return $next($request);
    }
}
