<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMessageReaction extends Model
{
    protected $fillable = [
        'group_message_id',
        'user_id',
        'reaction',
    ];

    public function groupMessage(): BelongsTo
    {
        return $this->belongsTo(GroupMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
