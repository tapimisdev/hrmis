<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupChatMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_chat_id',
        'user_id',
        'nickname',
        'added_by_id',
        'joined_at',
        'last_read_at',
        'cleared_before_message_id',
        'cleared_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by_id');
    }
}
