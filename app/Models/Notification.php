<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['sender', 'receiver', 'data', 'read'];

    protected $casts = [
        'data' => 'array', 
        'read' => 'boolean',
    ];
}
