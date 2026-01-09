<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForceUpdatePassword extends Controller
{
    public function index() {
        
        $user = Auth::user()->load('employeeInformation');
        $isForcedUpdate = $user->employeeInformation->toUpdatePassword ?? false;

       
        return response()->json([
            'status' => 'success',
            'isForcedUpdate' => $isForcedUpdate
        ]);
    }
}
