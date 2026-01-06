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
        '192.168.1.217', // UFace 402
        '192.168.110.12',
        '122.54.18.226',
        '127.0.0.1',
        '192.168.2.217',
        '192.168.2.19',
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
