<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantCertificate extends Model
{
    protected $guarded = [];
    protected $casts = ['issued_at' => 'date'];
}
