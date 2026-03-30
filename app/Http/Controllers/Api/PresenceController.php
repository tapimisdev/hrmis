<?php

namespace App\Http\Controllers\Api;

use App\Events\UserPresenceUpdated;
use App\Http\Controllers\Admin\Channels\OnlineUsersController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:online,offline'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $profile = app(OnlineUsersController::class)->getProfile($user);

        event(new UserPresenceUpdated(
            user: $profile,
            status: $request->string('status')->toString(),
            timestamp: now()->toISOString(),
        ));

        return response()->json([
            'status' => $request->string('status')->toString(),
            'user' => $profile,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
