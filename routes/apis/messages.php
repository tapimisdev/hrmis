<?php

use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\Api\GroupChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('direct-messages')->group(function () {
    Route::get('{user}', [DirectMessageController::class, 'index']);
    Route::get('{user}/info', [DirectMessageController::class, 'info']);
    Route::post('{user}/info', [DirectMessageController::class, 'updateInfo']);
    Route::get('{user}/media', [DirectMessageController::class, 'media']);
    Route::post('/', [DirectMessageController::class, 'store']);
    Route::patch('{message}', [DirectMessageController::class, 'update']);
    Route::delete('{message}', [DirectMessageController::class, 'destroy']);
    Route::delete('conversation/{user}', [DirectMessageController::class, 'destroyConversation']);
    Route::patch('{message}/reaction', [DirectMessageController::class, 'react']);
    Route::patch('{message}/pin', [DirectMessageController::class, 'pin']);
    Route::post('{user}/seen', [DirectMessageController::class, 'seen']);
    Route::post('{user}/typing', [DirectMessageController::class, 'typing']);
});

Route::prefix('group-chats')->group(function () {
    Route::post('/', [GroupChatController::class, 'store']);
    Route::delete('{groupChat}', [GroupChatController::class, 'destroy']);
    Route::get('{groupChat}', [GroupChatController::class, 'show']);
    Route::get('{groupChat}/media', [GroupChatController::class, 'media']);
    Route::post('{groupChat}/messages', [GroupChatController::class, 'storeMessage']);
    Route::post('{groupChat}/seen', [GroupChatController::class, 'seen']);
    Route::delete('{groupChat}/messages', [GroupChatController::class, 'destroyConversationMessages']);
    Route::post('{groupChat}/settings', [GroupChatController::class, 'updateSettings']);
    Route::post('{groupChat}/members', [GroupChatController::class, 'addMembers']);
    Route::post('{groupChat}/leave', [GroupChatController::class, 'leave']);
    Route::post('{groupChat}/typing', [GroupChatController::class, 'typing']);
    Route::post('{groupChat}/approve', [GroupChatController::class, 'approve']);
    Route::post('{groupChat}/reject', [GroupChatController::class, 'reject']);
});

Route::prefix('group-messages')->group(function () {
    Route::patch('{message}', [GroupChatController::class, 'updateMessage']);
    Route::delete('{message}', [GroupChatController::class, 'destroyMessage']);
    Route::patch('{message}/reaction', [GroupChatController::class, 'reactToMessage']);
    Route::patch('{message}/pin', [GroupChatController::class, 'pinMessage']);
});
