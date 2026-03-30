<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
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
        'read_at',
        'edited_at',
        'unsent_at',
        'unsent_by_id',
        'is_unsent',
    ];

    protected $casts = [
        'body' => 'encrypted',
        'read_at' => 'datetime',
        'pinned_at' => 'datetime',
        'edited_at' => 'datetime',
        'unsent_at' => 'datetime',
        'is_unsent' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function unsentBy()
    {
        return $this->belongsTo(User::class, 'unsent_by_id');
    }
}
