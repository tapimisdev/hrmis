<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WhitelistBiometricIP
{
    // allowed IPs
    protected $whitelisted = [
        '192.168.1.217', // Main door
        '192.168.2.218', // 2nd floor
        '192.168.2.219', // reserve
        '127.0.0.1',
        '192.168.2.231',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        if (!in_array($ip, $this->whitelisted)) {
            // Log the unauthorized access attempt
            Log::warning('Unauthorized IP attempt', [
                'ip' => $ip,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Unauthorized IP',
                'ip' => $ip
            ], 403);
        }

        return $next($request);
    }
}
