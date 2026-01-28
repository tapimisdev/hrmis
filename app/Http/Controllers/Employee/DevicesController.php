<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DevicesController extends Controller
{
    /**
     * Fetch active devices (sessions) for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $session_id = $request->session_id;

        $devices = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $session_id) 
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'ip' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                ];
            });

        return response()->json($devices);
    }

    /**
     * Delete a device (invalidate session).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $deleted = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Device logged out successfully']);
        }

        return response()->json(['error' => 'Device not found'], 404);
    }
}