<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Note extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'content', 'hasPin', 'pin'];

    protected $casts = [
        'hasPin' => 'boolean',
        'pin' => 'string', // Ensure it's treated as string
    ];

    protected $hidden = ['pin']; // Hide PIN from JSON responses

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Method to verify PIN
    public function verifyPin($pin)
    {
        return $this->pin && Hash::check($pin, $this->pin);
    }
}