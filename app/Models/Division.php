<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'division_manager_id',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'division_manager_id');
    }
}
