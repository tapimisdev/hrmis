<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_chat_id',
        'sender_id',
        'message_type',
        'body',
        'reply_to_id',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'attachment_extension',
        'attachment_type',
        'reaction',
        'pinned_at',
        'pinned_by_id',
        'edited_at',
        'unsent_at',
        'unsent_by_id',
        'is_unsent',
    ];

    protected $casts = [
        'body' => 'encrypted',
        'pinned_at' => 'datetime',
        'edited_at' => 'datetime',
        'unsent_at' => 'datetime',
        'is_unsent' => 'boolean',
    ];

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function pinnedBy()
    {
        return $this->belongsTo(User::class, 'pinned_by_id');
    }
}
