<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trails extends Model
{
    use HasFactory;

    protected $table = 'trails';

    protected $fillable = [
        'actioned_by_id',
        'actioned_by_name',
        'method',
        'controller',
        'description',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'actioned_by_id');
    }
}