<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessageReaction extends Model
{
    protected $fillable = [
        'direct_message_id',
        'user_id',
        'reaction',
    ];

    public function directMessage(): BelongsTo
    {
        return $this->belongsTo(DirectMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
