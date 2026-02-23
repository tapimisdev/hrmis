<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeavesApiController extends Controller
{
    public function getLeaves()
    {
        $leaves = DB::table('leaves')
            ->orderByDesc('is_cumulative')
            ->where('is_active', true)
            ->get();
            
        return response(['leaves' => $leaves, 'status' => 'get leaves api success']);
    }
}
