<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnnouncementPostedBy extends Model
{
    use HasFactory;

    protected $table = 'events_announcements_posted_by';

    protected $fillable = [
        'user_id',
        'event_announcement_id',
    ];

    public function event()
    {
        return $this->belongsTo(EventAnnouncement::class, 'event_announcement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
