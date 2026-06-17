<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantProfile extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['hired_at' => 'datetime', 'close_account_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function interests() { return $this->belongsToMany(WorkInterest::class, 'applicant_work_interest'); }
    public function education() { return $this->hasMany(ApplicantEducation::class); }
    public function workExperiences() { return $this->hasMany(ApplicantWorkExperience::class); }
    public function certificates() { return $this->hasMany(ApplicantCertificate::class); }
    public function applications() { return $this->hasMany(JobApplication::class); }
}
