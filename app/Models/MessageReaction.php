<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'message_type',
        'user_id',
        'reaction',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function directMessage()
    {
        return $this->belongsTo(DirectMessage::class, 'message_id', 'id');
    }

    public function groupMessage()
    {
        return $this->belongsTo(GroupMessage::class, 'message_id', 'id');
    }

    /**
     * Scope to get reactions for direct messages
     */
    public function scopeDirectMessages($query)
    {
        return $query->where('message_type', 'direct');
    }

    /**
     * Scope to get reactions for group messages
     */
    public function scopeGroupMessages($query)
    {
        return $query->where('message_type', 'group');
    }
}
