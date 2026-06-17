<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'scheduled_at' => 'datetime',
        'posted_until' => 'datetime',
    ];

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
