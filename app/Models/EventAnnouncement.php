<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnnouncement extends Model
{
    use HasFactory;

    protected $table = 'events_announcements';

    protected $fillable = [
        'title',
        'banner',
        'slug',
        'description',
        'posted_on',
        'email_notif',
        'push_notif',
        'show_viewers',
        'is_suspension',
    ];

    public function tags()
    {
        return $this->hasMany(EventAnnouncementTag::class);
    }

    public function attachments()
    {
        return $this->hasMany(EventAnnouncementAttachment::class);
    }

    public function posted_by()
    {
        return $this->belongsToMany(User::class, 'events_announcements_posted_by')
            ->withTimestamps();
    }

    public function viewers()
    {
        return $this->hasMany(EventAnnouncementViewer::class);
    }
}
