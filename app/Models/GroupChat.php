<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupChat extends Model
{
    use HasFactory;

    public const MAX_PENDING_REQUESTS_PER_USER = 5;

    protected $fillable = [
        'name',
        'photo_path',
        'created_by_id',
        'approval_status',
        'approval_level',
        'approved_by_id',
        'approved_at',
        'rejected_by_id',
        'rejected_at',
        'rejection_reason',
        'last_message_preview',
        'last_message_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function members()
    {
        return $this->hasMany(GroupChatMember::class);
    }

    public function messages()
    {
        return $this->hasMany(GroupMessage::class);
    }
}
