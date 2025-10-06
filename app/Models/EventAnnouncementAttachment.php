<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnnouncementAttachment extends Model
{
    use HasFactory;

    protected $table = 'events_announcements_attachments';

    protected $fillable = [
        'event_announcement_id',
        'filename',
        'title',
    ];

    public function event()
    {
        return $this->belongsTo(EventAnnouncement::class, 'event_announcement_id');
    }
}
