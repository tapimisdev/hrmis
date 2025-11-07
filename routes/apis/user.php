<?php
use Illuminate\Support\Facades\Route;

use App\Models\User;

Route::get('/users', function () {
    $users = User::role('hr')->get(); 
    return response()->json($users);
});