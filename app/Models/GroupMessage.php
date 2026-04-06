<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function reactions()
    {
        return $this->hasMany(GroupMessageReaction::class);
    }

    /**
     * Get reactions with user information formatted for frontend
     */
    public function getReactionsWithUsers()
    {
        return $this->reactions()
            ->with('user')
            ->get()
            ->map(function ($reaction) {
                $user = $reaction->user;
                $profile = $this->getUserProfileImageUrl($user);
                return [
                    'user_id' => $reaction->user_id,
                    'user_name' => $user->name,
                    'profile' => $profile,
                    'reaction' => $reaction->reaction,
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getUserProfileImageUrl($user)
    {
        // Try to get profile from employee_personal table
        if ($user && $user->id) {
            $employeeInfo = DB::table('employee_information')
                ->where('user_id', $user->id)
                ->first();
            
            if ($employeeInfo) {
                $employeePersonal = DB::table('employee_personal')
                    ->where('employee_no', $employeeInfo->employee_no)
                    ->first();
                
                if ($employeePersonal && $employeePersonal->profile) {
                    return Storage::url(
                        'public/users/' . $employeeInfo->employee_no . '/profile-image/' . $employeePersonal->profile
                    );
                }
            }
        }
        
        // Fallback to default avatar
        $name = $user->name ?? 'User';
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
    }
}
