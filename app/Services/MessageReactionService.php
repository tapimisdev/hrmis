<?php

namespace App\Services;

use App\Models\DirectMessage;
use App\Models\GroupMessage;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageReactionService
{
    /**
     * Add or update a user's reaction to a message
     */
    public function addReaction(string $messageType, int $messageId, int $userId, string $reaction): MessageReaction
    {
        $existingReaction = MessageReaction::where('message_id', $messageId)
            ->where('message_type', $messageType)
            ->where('user_id', $userId)
            ->first();

        if ($existingReaction) {
            $existingReaction->update(['reaction' => $reaction]);
            return $existingReaction;
        }

        return MessageReaction::create([
            'message_id' => $messageId,
            'message_type' => $messageType,
            'user_id' => $userId,
            'reaction' => $reaction,
        ]);
    }

    /**
     * Remove a user's reaction from a message
     */
    public function removeReaction(string $messageType, int $messageId, int $userId): bool
    {
        return (bool) MessageReaction::where('message_id', $messageId)
            ->where('message_type', $messageType)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Get all reactions for a message with user details
     */
    public function getReactionsWithUsers(string $messageType, int $messageId): array
    {
        return MessageReaction::where('message_id', $messageId)
            ->where('message_type', $messageType)
            ->with('user:id,name,profile_photo_path')
            ->get()
            ->map(function (MessageReaction $reaction) {
                return [
                    'user_id' => $reaction->user_id,
                    'user_name' => $reaction->user->name,
                    'avatar' => $reaction->user->profile_photo_path 
                        ? asset('storage/' . $reaction->user->profile_photo_path)
                        : null,
                    'reaction' => $reaction->reaction,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get grouped reactions (for tab display)
     */
    public function getGroupedReactions(string $messageType, int $messageId): array
    {
        $reactions = MessageReaction::where('message_id', $messageId)
            ->where('message_type', $messageType)
            ->get();

        return $reactions->groupBy('reaction')
            ->map(function (Collection $group) {
                return $group->count();
            })
            ->toArray();
    }

    /**
     * Clear all reactions for a message
     */
    public function clearAllReactions(string $messageType, int $messageId): bool
    {
        return (bool) MessageReaction::where('message_id', $messageId)
            ->where('message_type', $messageType)
            ->delete();
    }
}
