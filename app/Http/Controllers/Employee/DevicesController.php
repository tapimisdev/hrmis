<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DevicesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // Ensure Sanctum auth
    }

    /**
     * Fetch active devices (sessions) for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch active sessions for the user (excluding current session)
        $devices = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId()) // Exclude current session
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
    public function destroy(Request $request, $id, $action)
    {
        if ($action !== 'delete') {
            return response()->json(['error' => 'Invalid action'], 400);
        }

        $user = $request->user();

        // Find and delete the session (only if it belongs to the user)
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