<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnnouncementViewer extends Model
{
    use HasFactory;

    public $table = 'events_announcements_viewers';

    public $timestamps = false; // only has viewed_at

    protected $fillable = [
        'event_announcement_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
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
