<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnnouncementTag extends Model
{
    use HasFactory;

    public $table = 'events_announcements_tags';
    
    protected $fillable = [
        'event_announcement_id',
        'name',
    ];

    public function event()
    {
        return $this->belongsTo(EventAnnouncement::class, 'event_announcement_id');
    }
}
