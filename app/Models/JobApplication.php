<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $guarded = [];
    protected $casts = ['submitted_at' => 'datetime'];

    public function jobPosting() { return $this->belongsTo(JobPosting::class); }
    public function applicantProfile() { return $this->belongsTo(ApplicantProfile::class); }
    public function assessments() { return $this->hasMany(RecruitmentAssessment::class); }
    public function offer() { return $this->hasOne(JobOffer::class); }
    public function requirements() { return $this->hasMany(ApplicationRequirement::class); }
}
