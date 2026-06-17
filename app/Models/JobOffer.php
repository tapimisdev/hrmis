<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    protected $guarded = [];
    protected $casts = ['sent_at' => 'datetime', 'confirmed_at' => 'datetime'];
}
