<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentAssessment extends Model
{
    protected $guarded = [];
    protected $casts = ['scheduled_at' => 'datetime'];

    public function jobApplication() { return $this->belongsTo(JobApplication::class); }
    public function questions() { return $this->hasMany(RecruitmentAssessmentQuestion::class); }
}
