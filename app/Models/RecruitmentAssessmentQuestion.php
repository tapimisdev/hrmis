<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentAssessmentQuestion extends Model
{
    protected $guarded = [];
    protected $casts = ['options' => 'array'];
}
