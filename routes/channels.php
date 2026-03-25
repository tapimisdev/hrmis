<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\Channels\OnlineUsersController;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('refresh', function () {
    Log::info('--------------------------------- working sa channel ------------------------');
    return true;
});

Broadcast::channel('user.notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('direct-messages.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('online-users', function ($user) {
    return app(OnlineUsersController::class)->getProfile($user);
});
